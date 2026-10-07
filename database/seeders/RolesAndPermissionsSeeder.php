<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guards = ['web', 'sanctum'];

        $allPermissions = [
            // Issues
            'issues.view-any',
            'issues.view',
            'issues.create',
            'issues.update',
            'issues.publish',
            'issues.delete',

            // Articles
            'articles.view-any',
            'articles.view',
            'articles.create',
            'articles.update',
            'articles.submit-review',
            'articles.publish',
            'articles.delete',

            // Artists
            'artists.view-any',
            'artists.view',
            'artists.create',
            'artists.update',
            'artists.delete',

            // Media
            'media.view-any',
            'media.view',
            'media.upload',
            'media.delete',

            // System
            'system.manage-users',
            'system.manage-settings',
        ];

        $chiefEditorPermissions = [
            'issues.view-any',
            'issues.view',
            'issues.create',
            'issues.update',
            'issues.publish',
            'issues.delete',

            'articles.view-any',
            'articles.view',
            'articles.create',
            'articles.update',
            'articles.submit-review',
            'articles.publish',
            'articles.delete',

            'artists.view-any',
            'artists.view',
            'artists.create',
            'artists.update',
            'artists.delete',

            'media.view-any',
            'media.view',
            'media.upload',
            'media.delete',
        ];

        $sectionEditorPermissions = [
            'issues.view-any',
            'issues.view',

            'articles.view-any',
            'articles.view',
            'articles.create',
            'articles.update',
            'articles.submit-review',

            'artists.view-any',
            'artists.view',

            'media.view-any',
            'media.view',
        ];

        $mediaProducerPermissions = [
            'issues.view-any',
            'issues.view',

            'articles.view-any',
            'articles.view',

            'artists.view-any',
            'artists.view',

            'media.view-any',
            'media.view',
            'media.upload',
            'media.delete',
        ];

        foreach ($guards as $guard) {
            // Create permissions for this guard
            foreach ($allPermissions as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guard,
                ]);
            }

            // 1. super-admin
            Role::firstOrCreate([
                'name' => 'super-admin',
                'guard_name' => $guard,
            ]);

            // 2. chief-editor
            $chiefEditor = Role::firstOrCreate([
                'name' => 'chief-editor',
                'guard_name' => $guard,
            ]);
            $chiefEditor->syncPermissions($chiefEditorPermissions);

            // 3. section-editor
            $sectionEditor = Role::firstOrCreate([
                'name' => 'section-editor',
                'guard_name' => $guard,
            ]);
            $sectionEditor->syncPermissions($sectionEditorPermissions);

            // 4. media-producer
            $mediaProducer = Role::firstOrCreate([
                'name' => 'media-producer',
                'guard_name' => $guard,
            ]);
            $mediaProducer->syncPermissions($mediaProducerPermissions);
        }
    }
}
