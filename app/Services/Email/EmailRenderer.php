<?php

declare(strict_types=1);

namespace App\Services\Email;

final class EmailRenderer
{
    /**
     * @param array<string, mixed> $viewData
     */
    public function render(
        EmailDefinition $definition,
        array $viewData
    ): string {
        return view(
            $definition->viewName,
            $viewData
        );
    }

    public function renderPreview(
        EmailDefinition $definition
    ): string {
        return $this->render(
            $definition,
            $definition->previewData
        );
    }
}
