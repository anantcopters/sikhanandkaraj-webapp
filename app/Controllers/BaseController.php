<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Return validation errors passed through redirect flashdata.
     *
     * Only string field names and scalar error messages are returned. This
     * prevents unexpected session data from being passed directly to views.
     *
     * @return array<string, string>
     */
    protected function readValidationErrors(): array
    {
        $validationErrors = session(
            'validationErrors'
        );

        if (! is_array($validationErrors)) {
            return [];
        }

        $normalizedErrors = [];

        foreach ($validationErrors as $field => $message) {
            if (
                ! is_string($field)
                || ! is_scalar($message)
            ) {
                continue;
            }

            $normalizedErrors[$field] =
                (string) $message;
        }

        return $normalizedErrors;
    }

    /**
     * Return a form alert passed through redirect flashdata.
     *
     * Only string keys and scalar values are returned so arbitrary session
     * structures are not passed directly to a view.
     *
     * @return array<string, string>|null
     */
    protected function readFormAlert(): ?array
    {
        $formAlert = session(
            'formAlert'
        );

        if (! is_array($formAlert)) {
            return null;
        }

        $normalizedAlert = [];

        foreach ($formAlert as $key => $value) {
            if (
                ! is_string($key)
                || ! is_scalar($value)
            ) {
                continue;
            }

            $normalizedAlert[$key] =
                (string) $value;
        }

        return $normalizedAlert !== []
            ? $normalizedAlert
            : null;
    }

    /**
     * Return a scalar flashdata value as a string.
     */
    protected function readFlashString(
        string $key,
        string $default = ''
    ): string {
        $value = session($key);

        return is_scalar($value)
            ? (string) $value
            : $default;
    }
}
