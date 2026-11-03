<?php

/**
 * Cross-cutting UI strings shared across every module — common actions, statuses, and generic
 * labels. Module-specific vocabulary (e.g. "MRN", "Encounter") belongs in its own file
 * (lang/en/patients.php, etc.) rather than here.
 */
return [

    // Actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'create' => 'Create',
    'edit' => 'Edit',
    'update' => 'Update',
    'delete' => 'Delete',
    'view' => 'View',
    'search' => 'Search',
    'filter' => 'Filter',
    'export' => 'Export',
    'print' => 'Print',
    'import' => 'Import',
    'submit' => 'Submit',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'confirm' => 'Confirm',
    'back' => 'Back',
    'close' => 'Close',
    'download' => 'Download',
    'upload' => 'Upload',

    // Statuses
    'active' => 'Active',
    'inactive' => 'Inactive',
    'draft' => 'Draft',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed',
    'archived' => 'Archived',
    'deceased' => 'Deceased',

    // Common labels
    'name' => 'Name',
    'code' => 'Code',
    'description' => 'Description',
    'status' => 'Status',
    'actions' => 'Actions',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'no_results' => 'No results found.',
    'confirm_delete' => 'Are you sure you want to delete this?',

    // Navigation / menu section titles
    'nav' => [
        'dashboard' => 'Dashboard',
        'administration' => 'Administration',
        'security_and_audit' => 'Security & Audit',
        'system' => 'System',
    ],

    // Flash / feedback messages
    'messages' => [
        'created' => 'Created successfully.',
        'updated' => 'Updated successfully.',
        'deleted' => 'Deleted successfully.',
        'unauthorized' => 'You are not authorized to perform this action.',
    ],

];
