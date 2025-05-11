# LaraApprove

LaraApprove is a comprehensive approval management system designed to streamline the request approval process in Laravel applications. It provides a flexible and configurable workflow system for managing approvals with multiple levels, conditions, and contributors.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Implementing Models with Approval](#implementing-models-with-approval)
- [Setting Up Approval Workflows](#setting-up-approval-workflows)
- [Approval Hierarchy](#approval-hierarchy)
- [API Reference](#api-reference)

## Installation

### Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher

### Via Composer

```bash
composer require menma977/larapprove
```

### Publish Resources

After installing the package, publish its resources:

```bash
php artisan vendor:publish --tag=larapprove
```

This will publish:
- Migrations to `database/migrations`
- Configuration file to `config/lara_approve.php`

You can also publish specific resources:

```bash
# Publish only migrations
php artisan vendor:publish --tag=larapprove-migrations

# Publish only configuration
php artisan vendor:publish --tag=larapprove-config

# Publish only models
php artisan vendor:publish --tag=larapprove-models

# Publish only services
php artisan vendor:publish --tag=larapprove-services
```

### Run Migrations

```bash
php artisan migrate
```

## Configuration

After publishing the configuration file, you can modify it at `config/lara_approve.php`:

```php
return [
    /**
     * The model that will be used for approval. This should be set to the model class
     * that will perform approvals in your application, such as App\Models\User
     * or App\Models\Employee. Make sure this model exists in your application.
     */
    'user' => App\Models\User::class,

    /**
     * The models that can be approved. Add your models here to enable approval
     * functionality for them.
     */
    'models' => [
        App\Models\YourModel::class,
    ],
];
```

## Basic Usage

### Checking if a Model Can Be Approved

```php
$model = YourModel::find(1);
$canApprove = $model->can_approve; // Returns boolean
```

### Creating a Draft Approval

```php
use Menma977\Larapprove\Services\ApprovalService;

$model = YourModel::find(1);
$approvalEvent = ApprovalService::model($model)
    ->user(auth()->id())
    ->type('draft')
    ->status('draft')
    ->draft();
```

### Submitting an Approval

```php
use Menma977\Larapprove\Services\ApprovalService;

$model = YourModel::find(1);
$approvalEvent = ApprovalService::model($model)
    ->user(auth()->id())
    ->submit();
```

### Rejecting an Approval

```php
use Menma977\Larapprove\Services\ApprovalService;

$model = YourModel::find(1);
$approvalEvent = ApprovalService::model($model)
    ->user(auth()->id())
    ->reject();
```

### Rolling Back an Approval

```php
use Menma977\Larapprove\Services\ApprovalService;

$model = YourModel::find(1);
$approvalEvent = ApprovalService::model($model)
    ->user(auth()->id())
    ->rollback();
```

## Implementing Models with Approval

To make your model work with LaraApprove, add the `HasApproval` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Menma977\Larapprove\Traits\HasApproval;

class YourModel extends Model
{
    use HasApproval;

    // Your model implementation
}
```

Then, add your model to the `models` array in the `config/lara_approve.php` configuration file:

```php
'models' => [
    App\Models\YourModel::class,
],
```

## Setting Up Approval Workflows

### Creating an Approval Flow

An approval flow is the template for your approval process. It defines the overall structure of your approval workflow.

```php
use Menma977\Larapprove\Models\Flow;
use Menma977\Larapprove\Models\FlowComponent;

// Create a flow
$flow = Flow::create([
    'name' => 'Document Approval Flow',
    'description' => 'Flow for approving documents',
]);

// Add components to the flow
FlowComponent::create([
    'flow_id' => $flow->id,
    'model' => App\Models\YourModel::class,
    'name' => 'Document Approval',
]);
```

### Creating an Approval

An approval is an instance of a flow that can be used for actual approval processes.

```php
use Menma977\Larapprove\Models\Approval;
use Menma977\Larapprove\Helpers\ApprovalHelper;

// Create an approval
$approval = Approval::create([
    'name' => 'Document Approval',
    'flow_id' => $flow->id,
    'type' => ApprovalHelper::APPROVAL_TYPE_SEQUENTIAL, // or APPROVAL_TYPE_PARALLEL
]);
```

### Creating Approval Statements

Statements define conditions for when specific approval components should be used.

```php
use Menma977\Larapprove\Models\ApprovalStatement;

// Create a statement
$statement = ApprovalStatement::create([
    'approval_id' => $approval->id,
    'name' => 'Standard Document Approval',
    'is_default' => true,
]);
```

### Adding Conditions to Statements

Conditions determine when a statement should be applied based on the model's properties.

```php
use Menma977\Larapprove\Models\ApprovalCondition;

// Add a condition
ApprovalCondition::create([
    'approval_statement_id' => $statement->id,
    'field' => 'status',
    'operator' => '=',
    'value' => 'pending',
]);
```

### Creating Approval Components

Components represent individual steps in the approval process.

```php
use Menma977\Larapprove\Models\ApprovalComponent;
use Menma977\Larapprove\Helpers\ApprovalHelper;

// Create a component
$component = ApprovalComponent::create([
    'approval_statement_id' => $statement->id,
    'level' => 1,
    'type' => ApprovalHelper::CONTRIBUTOR_TYPE_AND, // or CONTRIBUTOR_TYPE_OR
    'name' => 'Department Manager Approval',
    'description' => 'Approval by the department manager',
    'color_code' => '#4287f5',
]);
```

### Adding Contributors to Components

Contributors are the entities (usually users) who can approve a component.

```php
use Menma977\Larapprove\Models\ApprovalContributor;

// Add a contributor
ApprovalContributor::create([
    'approval_component_id' => $component->id,
    'approvable_id' => 1, // User ID
    'approvable_type' => App\Models\User::class,
]);
```

## Approval Hierarchy

LaraApprove follows a hierarchical structure for managing approvals:

### Standard Hierarchy
```
Approval -> Statement -> Component -> Contributor
```

- **Approval**: The overall approval workflow
- **Statement**: Defines conditions for when specific components should be used
- **Component**: Individual steps in the approval process
- **Contributor**: Entities (users) who can approve a component

### Conditional Hierarchy
```
Approval -> Statement -> Condition -> Component -> Contributor
```

- **Condition**: Determines when a statement should be applied based on the model's properties

This hierarchy allows for flexible and complex approval workflows that can adapt to different scenarios based on the model's properties.

## API Reference

### ApprovalService

The `ApprovalService` class is the main entry point for interacting with the approval system.

#### Methods

- `model(Model $morphModel)`: Initialize the approval service with a model instance
- `user(int $userId)`: Set the user ID for the approval action
- `type(string $type)`: Set the type of the approval event
- `status(string $status)`: Set the status of the approval event
- `canApprove()`: Check if the current user can approve the current approval event component
- `draft()`: Create or retrieve a draft approval event
- `submit()`: Submit an approval for the current component
- `reject()`: Reject the current approval component
- `rollback()`: Rollback an approval event to draft status

### ApprovalHelper Constants

- `APPROVAL_TYPE_SEQUENTIAL`: Sequential approval type
- `APPROVAL_TYPE_PARALLEL`: Parallel approval type
- `CONTRIBUTOR_TYPE_AND`: All contributors must approve
- `CONTRIBUTOR_TYPE_OR`: Any contributor can approve
- `APPROVE_EVENT_DRAFT`: Draft status
- `APPROVE_EVENT_PENDING`: Pending status
- `APPROVE_EVENT_APPROVED`: Approved status
- `APPROVE_EVENT_REJECTED`: Rejected status
- `APPROVE_EVENT_ROLLBACK`: Rollback status
