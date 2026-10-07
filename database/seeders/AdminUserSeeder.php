<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = 'password';
        $hashedPassword = Hash::make($defaultPassword);

        $accounts = [
            [
                'name' => 'مدير النظام العام',
                'email' => 'admin@digimag.gov.ly',
                'role' => 'super-admin',
                'category_slug' => null,
            ],
            [
                'name' => 'رئيس التحرير التنفيذي',
                'email' => 'chief@digimag.gov.ly',
                'role' => 'chief-editor',
                'category_slug' => null,
            ],
            [
                'name' => 'محرر قسم السينما',
                'email' => 'editor.cinema@digimag.gov.ly',
                'role' => 'section-editor',
                'category_slug' => 'cinema',
            ],
            [
                'name' => 'محرر قسم المسرح',
                'email' => 'editor.theatre@digimag.gov.ly',
                'role' => 'section-editor',
                'category_slug' => 'theatre',
            ],
            [
                'name' => 'منتج الوسائط المتعددة',
                'email' => 'media@digimag.gov.ly',
                'role' => 'media-producer',
                'category_slug' => null,
            ],
        ];

        $summaryTable = [];

        foreach ($accounts as $accountData) {
            $user = User::updateOrCreate(
                ['email' => $accountData['email']],
                [
                    'name' => $accountData['name'],
                    'password' => $hashedPassword,
                ]
            );

            $user->syncRoles([$accountData['role']]);

            $categoryInfo = '—';
            if ($accountData['category_slug']) {
                $category = Category::where('slug', $accountData['category_slug'])->first();
                if ($category) {
                    $user->managedCategories()->syncWithoutDetaching([$category->id]);
                    $categoryInfo = "{$category->name} ({$category->slug})";
                } else {
                    $categoryInfo = "غير موجود ({$accountData['category_slug']})";
                }
            }

            $summaryTable[] = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $accountData['role'],
                'password' => $defaultPassword,
                'category' => $categoryInfo,
            ];
        }

        if (isset($this->command)) {
            $this->command->info('تم إنشاء وتحديث حسابات هيئة التحرير والإدارة بنجاح:');
            $this->command->table(
                ['الاسم الكامل', 'البريد الإلكتروني', 'الدور / الصلاحية', 'كلمة المرور', 'القسم المخصص'],
                $summaryTable
            );
        }
    }
}
