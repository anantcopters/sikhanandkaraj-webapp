<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\FieldOfficerModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Owns local SAK Volunteer verification-document storage.
 *
 * Files live outside public/ and can therefore only be delivered
 * through an authorised application endpoint.
 */
final class FieldOfficerDocumentService
{
    public const DOCUMENT_AADHAAR =
    'aadhaar';

    public const DOCUMENT_PAN =
    'pan';

    public const DOCUMENT_CANCELLED_CHEQUE =
    'cancelled_cheque';

    private const MAX_BYTES =
    1048576; // 1 MB

    /**
     * Browser upload field -> persisted database column.
     */
    private const DOCUMENT_COLUMNS = [
        self::DOCUMENT_AADHAAR =>
        'aadhaar_document',

        self::DOCUMENT_PAN =>
        'pan_document',

        self::DOCUMENT_CANCELLED_CHEQUE =>
        'cancelled_cheque_document',
    ];

    /**
     * Allowed client/server MIME values.
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    /**
     * Extension is derived from the validated MIME type rather than
     * trusting the user-supplied filename.
     */
    private const MIME_EXTENSIONS = [
        'application/pdf' =>
        'pdf',

        'image/jpeg' =>
        'jpg',

        'image/png' =>
        'png',
    ];

    public function __construct(
        private readonly FieldOfficerModel
        $fieldOfficerModel
    ) {}

    /**
     * Validate and persist the three mandatory registration files.
     *
     * If any file fails after another was already written, files written
     * during this request are deleted so a failed registration does not
     * leave orphaned new files.
     *
     * @param array<string, UploadedFile|null> $documents
     *
     * @return array{
     *     aadhaar_document:string,
     *     pan_document:string,
     *     cancelled_cheque_document:string
     * }
     */
    public function storeRegistrationDocuments(
        array $documents
    ): array {
        $stored = [];

        try {
            foreach (
                self::DOCUMENT_COLUMNS
                as $documentType => $column
            ) {
                $file =
                    $documents[$documentType]
                    ?? null;

                if (
                    !$file instanceof UploadedFile
                ) {
                    throw new RuntimeException(
                        $this->label($documentType)
                            . ' is required.'
                    );
                }

                $stored[$column] =
                    $this->storeFile(
                        $file,
                        $documentType
                    );
            }

            return $stored;
        } catch (Throwable $exception) {
            /*
             * These files were created by this failed request and are safe
             * to remove. Historical/replaced documents are never deleted.
             */
            foreach ($stored as $filename) {
                $this->deleteNewFileSafely(
                    (string) $filename
                );
            }

            throw $exception;
        }
    }

    /**
     * Remove newly-created files when the caller subsequently fails to
     * persist the SAK Volunteer database record.
     *
     * This is only for registration rollback; replaced historical files
     * must never be passed here.
     *
     * @param array<string, string> $filenames
     */
    public function rollbackNewDocuments(
        array $filenames
    ): void {
        foreach ($filenames as $filename) {
            $this->deleteNewFileSafely(
                $filename
            );
        }
    }

    /**
     * Super Admin document replacement.
     *
     * The old physical file is deliberately retained. Only the database
     * pointer is replaced with the newly generated filename.
     */
    public function replace(
        int $fieldOfficerId,
        string $documentType,
        UploadedFile $file
    ): void {
        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'Invalid SAK Volunteer.'
            );
        }

        $column =
            self::DOCUMENT_COLUMNS[$documentType]
            ?? null;

        if ($column === null) {
            throw new RuntimeException(
                'Invalid SAK Volunteer document type.'
            );
        }

        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveRecord(
                $fieldOfficerId
            );

        if (!is_array($fieldOfficer)) {
            throw new RuntimeException(
                'SAK Volunteer was not found.'
            );
        }

        /*
         * Store first. If DB update fails, only the newly-created file is
         * removed; the previously referenced document remains untouched.
         */
        $newFilename =
            $this->storeFile(
                $file,
                $documentType
            );

        try {
            if (
                $this->fieldOfficerModel
                ->update(
                    $fieldOfficerId,
                    [
                        $column =>
                        $newFilename,
                    ]
                ) === false
            ) {
                throw new RuntimeException(
                    'The SAK Volunteer document could not be updated.'
                );
            }
        } catch (Throwable $exception) {
            $this->deleteNewFileSafely(
                $newFilename
            );

            throw $exception;
        }

        /*
         * IMPORTANT:
         * Do not delete the previous filename.
         *
         * Requirement:
         * "Keep the original and replace the DB with new file name."
         */
    }

    /**
     * Resolve an authorised document for the Admin download controller.
     *
     * @return array{
     *     path:string,
     *     downloadName:string
     * }
     */
    public function resolveForDownload(
        int $fieldOfficerId,
        string $documentType
    ): array {
        $column =
            self::DOCUMENT_COLUMNS[$documentType]
            ?? null;

        if (
            $fieldOfficerId <= 0
            || $column === null
        ) {
            throw new RuntimeException(
                'Invalid SAK Volunteer document.'
            );
        }

        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveRecord(
                $fieldOfficerId
            );

        if (!is_array($fieldOfficer)) {
            throw new RuntimeException(
                'SAK Volunteer was not found.'
            );
        }

        /*
         * basename prevents any future malformed DB value from becoming
         * a path traversal vector.
         */
        $filename = basename(
            trim(
                (string) (
                    $fieldOfficer[$column]
                    ?? ''
                )
            )
        );

        if ($filename === '') {
            throw new RuntimeException(
                $this->label($documentType)
                    . ' is not available.'
            );
        }

        $path =
            $this->uploadDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        if (
            !is_file($path)
            || !is_readable($path)
        ) {
            throw new RuntimeException(
                'The requested document is not available.'
            );
        }

        return [
            'path' =>
            $path,

            'downloadName' =>
            $this->downloadFilename(
                $documentType,
                $filename
            ),
        ];
    }

    /**
     * Validate one uploaded file from server-observed properties and store
     * it under a random filename.
     */
    private function storeFile(
        UploadedFile $file,
        string $documentType
    ): string {
        if (
            !array_key_exists(
                $documentType,
                self::DOCUMENT_COLUMNS
            )
        ) {
            throw new RuntimeException(
                'Invalid SAK Volunteer document type.'
            );
        }

        if (
            !$file->isValid()
            || $file->hasMoved()
        ) {
            throw new RuntimeException(
                $this->label($documentType)
                    . ' upload is invalid.'
            );
        }

        $size =
            (int) $file->getSize();

        if (
            $size <= 0
            || $size > self::MAX_BYTES
        ) {
            throw new RuntimeException(
                $this->label($documentType)
                    . ' must not exceed 1 MB.'
            );
        }

        /*
         * getMimeType() inspects the temporary file rather than trusting
         * the browser's original extension.
         */
        $mimeType =
            strtolower(
                trim(
                    (string) $file->getMimeType()
                )
            );

        if (
            !in_array(
                $mimeType,
                self::ALLOWED_MIME_TYPES,
                true
            )
        ) {
            throw new RuntimeException(
                $this->label($documentType)
                    . ' must be a PDF, JPG/JPEG or PNG file.'
            );
        }

        $extension =
            self::MIME_EXTENSIONS[$mimeType]
            ?? null;

        if ($extension === null) {
            throw new RuntimeException(
                'Unsupported document format.'
            );
        }

        /*
         * Random binary name prevents user-controlled filenames and makes
         * stored object names non-guessable.
         */
        $filename =
            bin2hex(
                random_bytes(24)
            )
            . '.'
            . $extension;

        $directory =
            $this->uploadDirectory();

        if (
            !is_dir($directory)
            && !mkdir(
                $directory,
                0750,
                true
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'The SAK Volunteer document directory is unavailable.'
            );
        }

        $file->move(
            $directory,
            $filename
        );

        $storedPath =
            $directory
            . DIRECTORY_SEPARATOR
            . $filename;

        if (!is_file($storedPath)) {
            throw new RuntimeException(
                'The uploaded document could not be stored.'
            );
        }

        return $filename;
    }

    private function uploadDirectory(): string
    {
        return WRITEPATH
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'sak_volunteer_docs';
    }

    private function deleteNewFileSafely(
        string $filename
    ): void {
        $filename = basename(
            trim($filename)
        );

        if ($filename === '') {
            return;
        }

        $path =
            $this->uploadDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function label(
        string $documentType
    ): string {
        return match ($documentType) {
            self::DOCUMENT_AADHAAR =>
            'Aadhaar Card',

            self::DOCUMENT_PAN =>
            'PAN Card',

            self::DOCUMENT_CANCELLED_CHEQUE =>
            'Cancelled cheque copy',

            default =>
            'Document',
        };
    }

    private function downloadFilename(
        string $documentType,
        string $storedFilename
    ): string {
        $extension =
            strtolower(
                pathinfo(
                    $storedFilename,
                    PATHINFO_EXTENSION
                )
            );

        $base =
            match ($documentType) {
                self::DOCUMENT_AADHAAR =>
                'aadhaar-card',

                self::DOCUMENT_PAN =>
                'pan-card',

                self::DOCUMENT_CANCELLED_CHEQUE =>
                'cancelled-cheque',

                default =>
                'document',
            };

        return $base
            . (
                $extension !== ''
                ? '.' . $extension
                : ''
            );
    }
}
