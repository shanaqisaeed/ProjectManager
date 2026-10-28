<?php

namespace Database\Seeders;

use App\Enums\OrganizationNodeType;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for Markazi Electricity Distribution Company (MPEDC).
     */
    public function run(): void
    {
        // --------------------------------------------------------------------------
        // 1. Organization Tree Nodes (چارت سازمانی شرکت توزیع برق استان مرکزی)
        // --------------------------------------------------------------------------

        $rootOrg = OrganizationNode::create([
            'name' => 'شرکت توزیع نیروی برق استان مرکزی',
            'type' => OrganizationNodeType::ORGANIZATION->value,
            'description' => 'دفتر مرکزی و ستاد شرکت توزیع نیروی برق استان مرکزی',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        // معاونت‌ها و دفترهای اصلی
        $opsDept = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'معاونت بهره‌برداری و دیسپاچینگ',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'مدیریت و راهبری بهره‌برداری شبکه، مرکز دیسپاچینگ و فوریت‌های برق ۱۲۱',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $engDept = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'معاونت مهندسی و برنامه‌ریزی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'طراحی، برنامه‌ریزی، بودجه‌ریزی و نظارت بر توسعه شبکه‌های توزیع',
            'status' => 'ACTIVE',
            'sort_order' => 2,
        ]);

        $salesDept = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'معاونت خدمات مشترکین و فروش تار',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'فروش انشعاب، محاسبات صورتحساب و بازرسی لوازم اندازه‌گیری',
            'status' => 'ACTIVE',
            'sort_order' => 3,
        ]);

        $financeDept = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'معاونت مالی و پشتیبانی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'امور مالی، حسابداری، تدارکات و پشتیبانی منطقه‌ای',
            'status' => 'ACTIVE',
            'sort_order' => 4,
        ]);

        $itOffice = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'دفتر فناوری اطلاعات و آمار',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'مدیریت زیرساخت‌های فناوری اطلاعات، زیرسیستم‌های نرم‌افزاری و GIS',
            'status' => 'ACTIVE',
            'sort_order' => 5,
        ]);

        $arakDistrict = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'مدیریت توزیع برق شهرستان اراک',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'امور توزیع برق شهرستان اراک و نواحی تابعه',
            'status' => 'ACTIVE',
            'sort_order' => 6,
        ]);

        $savehDistrict = OrganizationNode::create([
            'parent_id' => $rootOrg->id,
            'name' => 'مدیریت توزیع برق شهرستان ساوه',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'امور توزیع برق شهرستان ساوه و شهرک‌های صنعتی',
            'status' => 'ACTIVE',
            'sort_order' => 7,
        ]);

        // واحدهای زیرمجموعه
        $dispatchUnit = OrganizationNode::create([
            'parent_id' => $opsDept->id,
            'name' => 'واحد مرکز کنترل و دیسپاچینگ',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'راهبری آنلاین شبکه و پاسخگویی حوادث',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $maintenanceUnit = OrganizationNode::create([
            'parent_id' => $opsDept->id,
            'name' => 'واحد تعمیرات و نگهداری شبکه',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'تعمیرات پیشگیرانه و خط گرم شبکه‌های فشار متوسط و ضعیف',
            'status' => 'ACTIVE',
            'sort_order' => 2,
        ]);

        $hseUnit = OrganizationNode::create([
            'parent_id' => $opsDept->id,
            'name' => 'واحد ایمنی و بهداشت (HSE)',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'نظارت بر ایمنی سیم‌بانان و مقررات محیط زیست',
            'status' => 'ACTIVE',
            'sort_order' => 3,
        ]);

        $designUnit = OrganizationNode::create([
            'parent_id' => $engDept->id,
            'name' => 'واحد طراحی و نظارت شبکه',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'تهیه طرح‌های توسعه و نظارت بر پیمانکاران',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $meterUnit = OrganizationNode::create([
            'parent_id' => $salesDept->id,
            'name' => 'واحد لوازم اندازه‌گیری و بازرسی کنتورها',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'تست، نصب و هوشمندسازی کنتورهای فهام و دیماندی',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $softwareUnit = OrganizationNode::create([
            'parent_id' => $itOffice->id,
            'name' => 'واحد توسعه سامانه و نرم‌افزار',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'توسعه سامانه‌های سازمانی، GIS و فرایندها',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $arakOpsUnit = OrganizationNode::create([
            'parent_id' => $arakDistrict->id,
            'name' => 'واحد بهره‌برداری و اتفاقات اراک',
            'type' => OrganizationNodeType::UNIT->value,
            'description' => 'رسیدگی به حوادث برق اراک',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        // تیم‌ها و گروه‌های عملیاتی
        $emergencyTeam = OrganizationNode::create([
            'parent_id' => $dispatchUnit->id,
            'name' => 'تیم فوریت‌های برق و اتفاقات (۱۲۱)',
            'type' => OrganizationNodeType::TEAM->value,
            'description' => 'گروه عملیاتی اکیپ‌های حوادث ۱۲۱',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $hotlineTeam = OrganizationNode::create([
            'parent_id' => $maintenanceUnit->id,
            'name' => 'تیم خط گرم (Hotline)',
            'type' => OrganizationNodeType::TEAM->value,
            'description' => 'تعمیرات شبکه ۲۰ کیلوولت بدون قطعی برق',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $gisTeam = OrganizationNode::create([
            'parent_id' => $softwareUnit->id,
            'name' => 'تیم توسعه سامانه GIS و مدیریت پروژه‌ها',
            'type' => OrganizationNodeType::TEAM->value,
            'description' => 'پیاده‌سازی لایه‌های مکانی شبکه و سامانه‌های مدیریتی',
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        // --------------------------------------------------------------------------
        // 2. Users & Role Assignments (کاربران و نقش‌های سازمانی)
        // --------------------------------------------------------------------------

        $defaultPassword = Hash::make('password123');

        // ۱. مدیر ارشد سیستم
        $superAdmin = User::create([
            'first_name' => 'رضا',
            'last_name' => 'کریمی',
            'username' => 'admin',
            'password_hash' => Hash::make('admin123'),
            'email' => 'admin@mpedc.ir',
            'mobile' => '09181110000',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $superAdmin->id,
            'role' => UserRole::SUPER_ADMIN->value,
            'organization_node_id' => $rootOrg->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۲. مدیرعامل سازمان
        $ceoUser = User::create([
            'first_name' => 'محمدرضا',
            'last_name' => 'مرادی',
            'username' => 'ceo_moradi',
            'password_hash' => $defaultPassword,
            'email' => 'moradi@mpedc.ir',
            'mobile' => '09181111111',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $ceoUser->id,
            'role' => UserRole::ORGANIZATION_MANAGER->value,
            'organization_node_id' => $rootOrg->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۳. معاون بهره‌برداری
        $opsManagerUser = User::create([
            'first_name' => 'امین',
            'last_name' => 'نادری',
            'username' => 'ops_manager',
            'password_hash' => $defaultPassword,
            'email' => 'naderi@mpedc.ir',
            'mobile' => '09182222222',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $opsManagerUser->id,
            'role' => UserRole::ORGANIZATION_MANAGER->value,
            'organization_node_id' => $opsDept->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۴. معاون مهندسی
        $engManagerUser = User::create([
            'first_name' => 'حسین',
            'last_name' => 'رضایی',
            'username' => 'eng_manager',
            'password_hash' => $defaultPassword,
            'email' => 'rezaei@mpedc.ir',
            'mobile' => '09183333333',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $engManagerUser->id,
            'role' => UserRole::ORGANIZATION_MANAGER->value,
            'organization_node_id' => $engDept->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۵. مدیر دفتر فناوری اطلاعات
        $itManagerUser = User::create([
            'first_name' => 'علی',
            'last_name' => 'فراهانی',
            'username' => 'it_manager',
            'password_hash' => $defaultPassword,
            'email' => 'farahani@mpedc.ir',
            'mobile' => '09184444444',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $itManagerUser->id,
            'role' => UserRole::UNIT_MANAGER->value,
            'organization_node_id' => $itOffice->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۶. مدیر توزیع برق ساوه
        $savehManagerUser = User::create([
            'first_name' => 'سعید',
            'last_name' => 'قاسمی',
            'username' => 'saveh_manager',
            'password_hash' => $defaultPassword,
            'email' => 'ghasemi@mpedc.ir',
            'mobile' => '09185555555',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $savehManagerUser->id,
            'role' => UserRole::UNIT_MANAGER->value,
            'organization_node_id' => $savehDistrict->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۷. سرپرست مرکز دیسپاچینگ
        $dispatchLeadUser = User::create([
            'first_name' => 'حامد',
            'last_name' => 'امیری',
            'username' => 'dispatch_lead',
            'password_hash' => $defaultPassword,
            'email' => 'amiri@mpedc.ir',
            'mobile' => '09186666666',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $dispatchLeadUser->id,
            'role' => UserRole::TEAM_LEAD->value,
            'organization_node_id' => $dispatchUnit->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۸. سرپرست تیم توسعه نرم‌افزار
        $devLeadUser = User::create([
            'first_name' => 'مریم',
            'last_name' => 'صادقی',
            'username' => 'dev_lead',
            'password_hash' => $defaultPassword,
            'email' => 'sadeghi@mpedc.ir',
            'mobile' => '09187777777',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $devLeadUser->id,
            'role' => UserRole::TEAM_LEAD->value,
            'organization_node_id' => $gisTeam->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۹. کارشناس ارشد لوازم اندازه‌گیری
        $meterExpertUser = User::create([
            'first_name' => 'مهدی',
            'last_name' => 'کاظمی',
            'username' => 'meter_expert',
            'password_hash' => $defaultPassword,
            'email' => 'kazemi@mpedc.ir',
            'mobile' => '09188888888',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $meterExpertUser->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $meterUnit->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۱۰. کارشناس GIS
        $gisExpertUser = User::create([
            'first_name' => 'زهرا',
            'last_name' => 'ابراهیمی',
            'username' => 'gis_expert',
            'password_hash' => $defaultPassword,
            'email' => 'ebrahimi@mpedc.ir',
            'mobile' => '09189999999',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $gisExpertUser->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $gisTeam->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۱۱. کارشناس ناظر طراحی
        $inspectorUser = User::create([
            'first_name' => 'علیرضا',
            'last_name' => 'نجفی',
            'username' => 'inspector_najafi',
            'password_hash' => $defaultPassword,
            'email' => 'najafi@mpedc.ir',
            'mobile' => '09181234567',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $inspectorUser->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $designUnit->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // ۱۲. اپراتور ۱۲۱
        $operatorUser = User::create([
            'first_name' => 'جواد',
            'last_name' => 'سلیمانی',
            'username' => '121_operator',
            'password_hash' => $defaultPassword,
            'email' => 'soleimani@mpedc.ir',
            'mobile' => '09187654321',
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $operatorUser->id,
            'role' => UserRole::MEMBER->value,
            'organization_node_id' => $emergencyTeam->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // --------------------------------------------------------------------------
        // 3. Project Permissions Seeder
        // --------------------------------------------------------------------------
        $this->call(ProjectPermissionSeeder::class);

        // --------------------------------------------------------------------------
        // 4. Sample Projects, Subprojects, Tasks, & Templates
        // --------------------------------------------------------------------------
        $this->call(SampleProjectDataSeeder::class);
    }
}
