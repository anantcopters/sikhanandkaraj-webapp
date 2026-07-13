# Create a Page from UI to Database

This guide shows the standard order for building a feature.

Example feature: **Edit Profile**.

## 1. Decide the feature flow

Write the flow before coding:

```text
Open form
  ↓
Load existing profile
  ↓
Submit form
  ↓
Client validation
  ↓
Server validation
  ↓
Update database
  ↓
Redirect with success message
```

Identify:

- fields;
- validation rules;
- tables involved;
- business rules;
- success destination;
- field errors and form-level errors.

## 2. Add routes

In `app/Config/Routes.php`:

```php
$routes->get('profile/edit', 'ProfileController::edit', [
    'as' => 'web.profile.edit',
]);

$routes->post('profile/edit', 'ProfileController::update', [
    'as' => 'web.profile.update',
]);
```

Use GET to display and POST to change data.

## 3. Add or update SQL

For a new installation, update `sql/schema`.

For a database already in use, add a new file under `sql/updates`:

```text
sql/updates/20260713_001_add_profile_fields.sql
```

Do not edit an update file after it has been deployed.

## 4. Create the model

Create `app/Models/ProfileModel.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class ProfileModel extends Model
{
    protected $table = 'profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'date_of_birth',
        'city_id',
        'about_me',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
```

Add model methods only for table-specific queries.

## 5. Create validation

Create `app/Validation/ProfileValidation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Validation;

final class ProfileValidation
{
    public static function updateRules(): array
    {
        return [
            'city_id' => [
                'label' => 'City',
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => 'Please select a city.',
                ],
            ],
            'about_me' => [
                'label' => 'About me',
                'rules' => ['permit_empty', 'max_length[1000]'],
            ],
        ];
    }

    private function __construct()
    {
    }
}
```

## 6. Create the service

Use a service when the feature has business rules, several models or a transaction.

Create `app/Services/Profile/ProfileService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\ProfileModel;
use RuntimeException;

final class ProfileService
{
    public function __construct(
        private readonly ProfileModel $profileModel
    ) {
    }

    public function update(int $userId, array $data): void
    {
        $profile = $this->profileModel
            ->where('user_id', $userId)
            ->first();

        if (!is_array($profile)) {
            throw new RuntimeException('Profile was not found.');
        }

        if ($this->profileModel->update((int) $profile['id'], $data) === false) {
            throw new RuntimeException('Profile could not be updated.');
        }
    }
}
```

Register reusable services in `app/Config/Services.php`.

## 7. Create the controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Validation\ProfileValidation;
use CodeIgniter\HTTP\RedirectResponse;

final class ProfileController extends BaseController
{
    public function edit(): string
    {
        return view('Pages/Profile/Edit', [
            'pageTitle' => 'Edit Profile',
            'pageScripts' => [
                'assets/js/pages/profile-edit.js',
            ],
        ]);
    }

    public function update(): RedirectResponse
    {
        $input = [
            'city_id' => trim((string) $this->request->getPost('city_id')),
            'about_me' => trim((string) $this->request->getPost('about_me')),
        ];

        $validation = service('validation');
        $validation->setRules(ProfileValidation::updateRules());

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', $validation->getErrors());
        }

        try {
            service('profileService')->update(
                (int) session('user_id'),
                $validation->getValidated()
            );

            return redirect()
                ->to(route_to('web.profile.edit'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Profile updated',
                    'message' => 'Your changes were saved successfully.',
                ]);
        } catch (\Throwable $exception) {
            log_message('error', 'Profile update failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Update failed',
                    'message' => 'Your profile could not be updated.',
                ]);
        }
    }
}
```

## 8. Create the view

Create `app/Views/Pages/Profile/Edit.php`.

At the top, resolve errors and alerts once. Use:

- `route_to()` for the action;
- `csrf_field()`;
- `old()` for values;
- `FieldError` below fields;
- `FormAlert` above the form;
- `data-validate` and `novalidate` on the form.

## 9. Add JavaScript

Create `public/assets/js/pages/profile-edit.js` only for page-specific behaviour.

Reusable validation is already handled by `form-validator.js`. Choices.js is handled by `select-choice.js`.

## 10. Add CSS only when needed

Search `app.css` first. Add only missing, page-specific styles to `custom.css`.

## 11. Test

Test the complete flow, not only the successful path:

- direct page access;
- empty form;
- invalid form;
- old values after redirect;
- database record missing;
- database query failure;
- duplicate submission;
- success alert;
- mobile layout;
- keyboard and screen-reader labels.