<?php

namespace Database\Seeders;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateApprovalStage;
use App\Models\ProjectTemplateRole;
use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskSupervisor;
use App\Models\Task;
use App\Models\TaskChat;
use App\Models\TaskSupervisor;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleProjectDataSeeder extends Seeder
{
    public function run(): void
    {
        // Find existing users from DatabaseSeeder
        $superAdmin = User::where('username', 'admin')->first() ?? User::first();
        $opsManager = User::where('username', 'ops_manager')->first() ?? $superAdmin;
        $engManager = User::where('username', 'eng_manager')->first() ?? $superAdmin;
        $itManager = User::where('username', 'it_manager')->first() ?? $superAdmin;
        $savehManager = User::where('username', 'saveh_manager')->first() ?? $superAdmin;
        $dispatchLead = User::where('username', 'dispatch_lead')->first() ?? $superAdmin;
        $devLead = User::where('username', 'dev_lead')->first() ?? $superAdmin;
        $meterExpert = User::where('username', 'meter_expert')->first() ?? $superAdmin;
        $gisExpert = User::where('username', 'gis_expert')->first() ?? $superAdmin;
        $inspector = User::where('username', 'inspector_najafi')->first() ?? $superAdmin;
        $operator = User::where('username', '121_operator')->first() ?? $superAdmin;

        // Organization nodes
        $meterUnit = OrganizationNode::where('name', 'like', '%لوازم اندازه‌گیری%')->first() ?? OrganizationNode::first();
        $softwareUnit = OrganizationNode::where('name', 'like', '%توسعه سامانه%')->first() ?? OrganizationNode::first();
        $savehDistrict = OrganizationNode::where('name', 'like', '%ساوه%')->first() ?? OrganizationNode::first();
        $arakDistrict = OrganizationNode::where('name', 'like', '%اراک%')->first() ?? OrganizationNode::first();
        $maintenanceUnit = OrganizationNode::where('name', 'like', '%تعمیرات%')->first() ?? OrganizationNode::first();

        // =========================================================================
        // 1. پروژه کلان ۱: طرح جامع هوشمندسازی شبکه و نصب کنتورهای فهام استان
        // =========================================================================
        $fahamProject = Project::firstOrCreate(
            ['project_key' => 'PRJ-FAHAM-1405'],
            [
                'organization_node_id' => $meterUnit->id,
                'title' => 'طرح جامع هوشمندسازی شبکه و نصب کنتورهای هوشمند فهام استان مرکزی',
                'description' => 'پروژه استراتژیک استقرار کنتورهای هوشمند دیماندی، تجاری و صنعتی به همراه مرکز پایش برخط بار شبکه و مدیریت مصرف تابستانه.',
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'deadline' => now()->addDays(90)->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::FORCE_MAJEURE->value,
                'created_by' => $superAdmin->id,
            ]
        );

        // Members
        ProjectMember::firstOrCreate(
            ['project_id' => $fahamProject->id, 'user_id' => $meterExpert->id],
            [
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'cooperation_title' => 'مدیر پروژه هوشمندسازی فهام',
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now()->subDays(30),
            ]
        );

        ProjectMember::firstOrCreate(
            ['project_id' => $fahamProject->id, 'user_id' => $inspector->id],
            [
                'project_role' => ProjectMemberRole::PROJECT_SUPERVISOR->value,
                'cooperation_title' => 'سرپرست نظارت فنی و تحویل قطعات',
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now()->subDays(30),
            ]
        );

        ProjectMember::firstOrCreate(
            ['project_id' => $fahamProject->id, 'user_id' => $opsManager->id],
            [
                'project_role' => ProjectMemberRole::PROJECT_SUPERVISOR->value,
                'cooperation_title' => 'ناظر عالیه بهره‌برداری',
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now()->subDays(30),
            ]
        );

        // Project Approval Stages
        $stage1 = ProjectApprovalStage::firstOrCreate(
            ['project_id' => $fahamProject->id, 'stage_number' => 1],
            [
                'stage_name' => 'تایید مشخصات فنی و آزمایشگاهی کنتورها',
                'supervisor_user_id' => $inspector->id,
                'status' => 'APPROVED',
                'approved_at' => now()->subDays(15),
                'comments' => 'کلیه استانداردهای توانیر و پروتکل DLMS تایید گردید.',
            ]
        );

        $stage2 = ProjectApprovalStage::firstOrCreate(
            ['project_id' => $fahamProject->id, 'stage_number' => 2],
            [
                'stage_name' => 'تایید پایلوت ارتباطی مودم‌های قرائت از راه دور',
                'supervisor_user_id' => $opsManager->id,
                'status' => 'PENDING',
            ]
        );

        $stage3 = ProjectApprovalStage::firstOrCreate(
            ['project_id' => $fahamProject->id, 'stage_number' => 3],
            [
                'stage_name' => 'تایید نهایی تحویل میدانی و اتصال به دیسپاچینگ',
                'supervisor_user_id' => $engManager->id,
                'status' => 'PENDING',
            ]
        );

        // ---------------------------------------------------------------------
        // تسک کلان ۱ پروژه اصلی: تامین تجهیزات و کنتورهای هوشمند فهام
        // ---------------------------------------------------------------------
        $mainTask1 = Task::create([
            'project_id' => $fahamProject->id,
            'title' => 'تامین و تحویل انبار ۵۰۰۰ دستگاه کنتور هوشمند سه‌فاز',
            'description' => 'فرایند مناقصه، خرید، آزمون آزمایشگاهی و تحویل فیزیکی به انبار مرکزی اراک',
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now()->subDays(5),
            'executor_user_id' => $meterExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $superAdmin->id,
        ]);

        TaskSupervisor::create([
            'task_id' => $mainTask1->id,
            'project_approval_stage_id' => $stage1->id,
            'stage_number' => 1,
            'stage_name' => 'تاییدیه انبار و تست اولیه',
            'user_id' => $inspector->id,
            'status' => 'APPROVED',
            'comments' => 'تحویل کالا مطابق فاکتور و آزمون کیفی تایید شد.',
            'action_at' => now()->subDays(5),
        ]);

        // زیرتسک‌های تسک کلان ۱
        $subTask1_1 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask1->id,
            'title' => 'برگزاری مناقصه عمومی و انتخاب تامین‌کننده کنتور هوشمند',
            'description' => 'ارزیابی کیفی اسناد مناقصه، بازگشایی پاکات و عقد قرارداد با شرکت سازنده',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'due_date' => now()->subDays(20)->format('Y-m-d'),
            'completed_at' => now()->subDays(20),
            'executor_user_id' => $meterExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        $subTask1_2 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask1->id,
            'title' => 'نمونه‌برداری تصادفی و انجام تست‌های تایپ و روتین در آزمایشگاه مرجع',
            'description' => 'بررسی دقت کلاس اندازه‌گیری ۰.۵S و مقاومت عایقی در آزمایشگاه کالیبراسیون',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(19)->format('Y-m-d'),
            'due_date' => now()->subDays(8)->format('Y-m-d'),
            'completed_at' => now()->subDays(8),
            'executor_user_id' => $inspector->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        TaskChat::create([
            'task_id' => $subTask1_2->id,
            'user_id' => $inspector->id,
            'message' => 'کلیه تست‌های تایپ و آزمون ضربه صاعقه با موفقیت پاس شد. گواهی آزمایشگاه پیوست شد.',
        ]);

        $subTask1_3 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask1->id,
            'title' => 'ورود به انبار مرکزی و ثبت سریال‌ها در سامانه انبارداری',
            'description' => 'بارکدگذاری، تفکیک بر اساس نوع CT و ثبت رسید انبار',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now()->subDays(5),
            'executor_user_id' => $meterExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        // ---------------------------------------------------------------------
        // تسک کلان ۲ پروژه اصلی: راه‌اندازی زیرساخت MDM و سرورهای مرکزی
        // ---------------------------------------------------------------------
        $mainTask2 = Task::create([
            'project_id' => $fahamProject->id,
            'title' => 'نصب و راه‌اندازی سرور مرکزی MDM و پایش بلادرنگ بار',
            'description' => 'پیکربندی نرم‌افزار مدیریت داده‌های اندازه‌گیری (MDM/HES) در دیتاسنتر ستاد شرکت',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'due_date' => now()->addDays(25)->format('Y-m-d'),
            'executor_user_id' => $devLead->id,
            'version' => 'v2.1.0',
            'created_by' => $superAdmin->id,
        ]);

        TaskSupervisor::create([
            'task_id' => $mainTask2->id,
            'project_approval_stage_id' => $stage2->id,
            'stage_number' => 1,
            'stage_name' => 'تایید فنی زیرساخت شبکه و امنیت',
            'user_id' => $itManager->id,
            'status' => 'APPROVED',
            'comments' => 'فایروال و شبکه اختصاصی APN با موفقیت راه‌اندازی شد.',
            'action_at' => now()->subDays(2),
        ]);

        TaskSupervisor::create([
            'task_id' => $mainTask2->id,
            'project_approval_stage_id' => $stage3->id,
            'stage_number' => 2,
            'stage_name' => 'تایید پایانی اتصال به سامانه دیسپاچینگ',
            'user_id' => $opsManager->id,
            'status' => 'PENDING',
        ]);

        // زیرتسک‌های تسک کلان ۲
        $subTask2_1 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask2->id,
            'title' => 'استقرار سرورهای فیزیکی و پایگاه داده اوراکل MDM در دیتاسنتر',
            'description' => 'نصب سخت‌افزار سرورهای HPE، کانفیگ SAN Storage و نصب کلاستر Oracle RAC',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'completed_at' => now()->subDays(3),
            'executor_user_id' => $devLead->id,
            'version' => 'v2.1.0',
            'created_by' => $devLead->id,
        ]);

        $subTask2_2 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask2->id,
            'title' => 'تست امنیتی تبادل داده و رمزنگاری پکت‌های DLMS از بستر APN',
            'description' => 'پیکربندی کلیدهای رمزنگاری AES-128 GCM و تست پکت‌های قرائت از راه دور',
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(2)->format('Y-m-d'),
            'due_date' => now()->addDays(8)->format('Y-m-d'),
            'executor_user_id' => $devLead->id,
            'version' => 'v2.1.0',
            'created_by' => $devLead->id,
        ]);

        TaskChat::create([
            'task_id' => $subTask2_2->id,
            'user_id' => $devLead->id,
            'message' => 'پروتکل امنیتی تست شد؛ تاخیر تبادل پکت‌ها کمتر از ۱۰۰ میلی‌ثانیه است.',
        ]);

        TaskChat::create([
            'task_id' => $subTask2_2->id,
            'user_id' => $meterExpert->id,
            'message' => 'عالیه، لطفاً تست ارسال فرمان قطع دیسکانکتور رو هم با مودم‌های تستی انجام بدید.',
        ]);

        $subTask2_3 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask2->id,
            'title' => 'پیاده‌سازی وب‌سرویس RESTful اتصال دیسپاچینگ جهت اعمال دیسکانکتور',
            'description' => 'ارائه وب‌سرویس با مستندات Swagger جهت فراخوانی قطع و وصل اضطراری بار',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::TODO->value,
            'start_date' => now()->addDays(9)->format('Y-m-d'),
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'executor_user_id' => $devLead->id,
            'version' => 'v2.2.0',
            'created_by' => $devLead->id,
        ]);

        $subTask2_4 = Task::create([
            'project_id' => $fahamProject->id,
            'parent_id' => $mainTask2->id,
            'title' => 'طراحی داشبورد مدیریتی مانیتورینگ مصرف لحظه‌ای و هشدارهای سرقت بار',
            'description' => 'داشبورد بلادرنگ با نمودارهای مصرف دیماند، ضریب توان و هشدار باز شدن درب ترمینال',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TODO->value,
            'start_date' => now()->addDays(12)->format('Y-m-d'),
            'due_date' => now()->addDays(25)->format('Y-m-d'),
            'executor_user_id' => $devLead->id,
            'version' => 'v2.2.0',
            'created_by' => $devLead->id,
        ]);

        // =========================================================================
        // 1.1 زیرپروژه اول: هوشمندسازی کنتورهای شهرک صنعتی کاوه ساوه
        // =========================================================================
        $savehSubProject = Project::firstOrCreate(
            ['project_key' => 'PRJ-FAHAM-SAVEH-01'],
            [
                'parent_project_id' => $fahamProject->id,
                'organization_node_id' => $savehDistrict->id,
                'title' => 'زیرپروژه هوشمندسازی لوازم اندازه‌گیری مشترکین دیماندی شهرک صنعتی کاوه ساوه',
                'description' => 'تعویض کنتورها و نصب سیستم‌های پایش از راه دور مشترکین صنعتی دیماندی شهرک صنعتی کاوه ساوه.',
                'start_date' => now()->subDays(20)->format('Y-m-d'),
                'deadline' => now()->addDays(60)->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::HIGH->value,
                'created_by' => $meterExpert->id,
            ]
        );

        ProjectMember::firstOrCreate(
            ['project_id' => $savehSubProject->id, 'user_id' => $savehManager->id],
            [
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'cooperation_title' => 'مدیر اجرایی طرح ساوه',
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now()->subDays(20),
            ]
        );

        // تسک ۱ در زیرپروژه ساوه
        $savehTask1 = Task::create([
            'project_id' => $savehSubProject->id,
            'title' => 'ممیزی و آماربرداری میدانی پست‌های اختصاصی کارخانجات شهرک کاوه',
            'description' => 'بررسی مشخصات CT و PT ترانس‌های مشترکین دیماندی بالای ۵۰۰ کیلووات',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(20)->format('Y-m-d'),
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now()->subDays(5),
            'executor_user_id' => $savehManager->id,
            'version' => 'v1.0.0',
            'created_by' => $savehManager->id,
        ]);

        Task::create([
            'project_id' => $savehSubProject->id,
            'parent_id' => $savehTask1->id,
            'title' => 'بازدید و ثبت اطلاعات فنی ترانس‌های اختصاصی فاز ۱ شهرک کاوه',
            'description' => 'مستندسازی وضعیت تابلوهای توزیع و نسبت تبدیل ترانس‌های جریان',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(20)->format('Y-m-d'),
            'due_date' => now()->subDays(12)->format('Y-m-d'),
            'completed_at' => now()->subDays(12),
            'executor_user_id' => $savehManager->id,
            'version' => 'v1.0.0',
            'created_by' => $savehManager->id,
        ]);

        Task::create([
            'project_id' => $savehSubProject->id,
            'parent_id' => $savehTask1->id,
            'title' => 'تست سلامت مدار ولتاژ و جریان (CT/PT) با دستگاه تست پرتابل',
            'description' => 'اطمینان از عدم دستکاری و صحت پلاریته ترمینال‌ها',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(11)->format('Y-m-d'),
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now()->subDays(5),
            'executor_user_id' => $inspector->id,
            'version' => 'v1.0.0',
            'created_by' => $savehManager->id,
        ]);

        // تسک ۲ در زیرپروژه ساوه
        $savehTask2 = Task::create([
            'project_id' => $savehSubProject->id,
            'title' => 'تعویض و نصب کنتورهای دیجیتال مجهز به مودم ۴G در فاز ۱ شهرک',
            'description' => 'نصب ۳۵۰ دستگاه کنتور با هماهنگی مدیریت مصرف شهرک صنعتی و اعمال پلمب‌های امنیتی',
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(4)->format('Y-m-d'),
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'executor_user_id' => $savehManager->id,
            'version' => 'v1.1.0',
            'created_by' => $savehManager->id,
        ]);

        TaskSupervisor::create([
            'task_id' => $savehTask2->id,
            'stage_number' => 1,
            'stage_name' => 'تایید پلمب امنیتی و اتصال آنلاین',
            'user_id' => $inspector->id,
            'status' => 'PENDING',
        ]);

        Task::create([
            'project_id' => $savehSubProject->id,
            'parent_id' => $savehTask2->id,
            'title' => 'هماهنگی خاموشی موضعی و تعویض فیزیکی کنتورهای دیماندی',
            'description' => 'برنامه‌ریزی ساعات کم‌باری جهت تعویض بدون اختلال در خطوط تولید کارخانجات',
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(4)->format('Y-m-d'),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'executor_user_id' => $savehManager->id,
            'version' => 'v1.1.0',
            'created_by' => $savehManager->id,
        ]);

        Task::create([
            'project_id' => $savehSubProject->id,
            'parent_id' => $savehTask2->id,
            'title' => 'نصب آنتن‌های تقویت سیگنال و پلمب بارکدی ترانس‌ها',
            'description' => 'نصب آنتن خارجی در پست‌های زمینی دارای ضعف آنتن‌دهی و ثبت شماره پلمب',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TODO->value,
            'start_date' => now()->addDays(11)->format('Y-m-d'),
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'executor_user_id' => $inspector->id,
            'version' => 'v1.1.0',
            'created_by' => $savehManager->id,
        ]);

        // =========================================================================
        // 1.2 زیرپروژه دوم: هوشمندسازی کنتورهای دیماندی و اداری اراک
        // =========================================================================
        $arakSubProject = Project::firstOrCreate(
            ['project_key' => 'PRJ-FAHAM-ARAK-02'],
            [
                'parent_project_id' => $fahamProject->id,
                'organization_node_id' => $arakDistrict->id,
                'title' => 'زیرپروژه نصب کنتورهای هوشمند فهام مشترکین اداری و تجاری اراک',
                'description' => 'هوشمندسازی کنتورهای کلیه ادارات کل، بیمارستان‌ها و مراکز تجاری بزرگ شهر اراک.',
                'start_date' => now()->subDays(15)->format('Y-m-d'),
                'deadline' => now()->addDays(50)->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::HIGH->value,
                'created_by' => $meterExpert->id,
            ]
        );

        $arakTask1 = Task::create([
            'project_id' => $arakSubProject->id,
            'title' => 'نصب کنتور هوشمند و ترانس‌های جریان در ادارات دولتی اراک',
            'description' => 'پایش آنلاین مصرف برق دستگاه‌های اجرایی طبق مصوبه هیات دولت جهت پایش پیک بار',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'executor_user_id' => $inspector->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        Task::create([
            'project_id' => $arakSubProject->id,
            'parent_id' => $arakTask1->id,
            'title' => 'مکاتبه رسمی و تعیین زمان‌بندی تعویض کنتور با ادارات کل استان',
            'description' => 'ارسال ابلاغیه توانیر و جدول زمان‌بندی مراجعه کارشناسان فنی',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::COMPLETED->value,
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'due_date' => now()->subDays(8)->format('Y-m-d'),
            'completed_at' => now()->subDays(8),
            'executor_user_id' => $meterExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        Task::create([
            'project_id' => $arakSubProject->id,
            'parent_id' => $arakTask1->id,
            'title' => 'نصب کنتورهای هوشمند در ادارات اصلی (استانداری، علوم پزشکی، مخابرات)',
            'description' => 'نصب کنتورهای تابلویی مجهز به مودم داخلی و ارتباط بی‌سیم',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'executor_user_id' => $inspector->id,
            'version' => 'v1.0.0',
            'created_by' => $meterExpert->id,
        ]);

        // =========================================================================
        // 2. پروژه کلان ۲: توسعه سامانه مدیریت حوادث و مانیتورینگ مکانی شبکه (GIS)
        // =========================================================================
        $gisProject = Project::firstOrCreate(
            ['project_key' => 'PRJ-GIS-DISPATCH-01'],
            [
                'organization_node_id' => $softwareUnit->id,
                'title' => 'توسعه سامانه مکانی GIS و اتصال برخط به مرکز پاسخگویی ۱۲۱',
                'description' => 'پیاده‌سازی وب‌سرویس‌های یکپارچه لایه‌های مکانی خطوط فشار متوسط، پست‌های توزیع و ردیابی ناوگان اکیپ‌های حوادث ۱۲۱.',
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'deadline' => now()->addDays(60)->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::HIGH->value,
                'created_by' => $itManager->id,
            ]
        );

        ProjectMember::firstOrCreate(
            ['project_id' => $gisProject->id, 'user_id' => $devLead->id],
            [
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'cooperation_title' => 'مدیر فنی سامانه GIS',
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now()->subDays(10),
            ]
        );

        $gisTask1 = Task::create([
            'project_id' => $gisProject->id,
            'title' => 'برداشت دقیق GPS پایه‌های خط ۲۰ کیلوولت فیدر خنداب',
            'description' => 'نقشه‌برداری مکانی با دستگاه‌های GNSS دوفرکانسه و تطبیق با تصاویر ماهواره‌ای',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'due_date' => now()->addDays(12)->format('Y-m-d'),
            'executor_user_id' => $gisExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $devLead->id,
        ]);

        Task::create([
            'project_id' => $gisProject->id,
            'parent_id' => $gisTask1->id,
            'title' => 'برداشت نقاط مکانی پایه‌ها و سکسیونرهای هوایی خط خنداب',
            'description' => 'برداشت ۱۲۰۰ نقطه مکانی به همراه ویژگی‌های عایقی و نوع هادی',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'executor_user_id' => $gisExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $devLead->id,
        ]);

        Task::create([
            'project_id' => $gisProject->id,
            'parent_id' => $gisTask1->id,
            'title' => 'ورود اطلاعات به پایگاه داده مکانی ArcGIS Server و ایجاد لایه توپولوژی',
            'description' => 'تطبیق عوارض شبکه، کدگذاری شین‌ها و تست صحت اتصالات الکتریکی',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TODO->value,
            'start_date' => now()->addDays(6)->format('Y-m-d'),
            'due_date' => now()->addDays(12)->format('Y-m-d'),
            'executor_user_id' => $gisExpert->id,
            'version' => 'v1.0.0',
            'created_by' => $devLead->id,
        ]);

        // =========================================================================
        // 3. تمپلیت‌های آماده نمونه (Sample Project Templates)
        // =========================================================================
        $sampleTemplate = ProjectTemplate::firstOrCreate(
            ['title' => 'الگوی استاندارد احداث پست توزیع هوایی و توسعه خط فشار متوسط'],
            [
                'organization_node_id' => $meterUnit->id,
                'description' => 'تمپلیت آماده مهندسی شامل مراحل خرید ترانس، احداث پایه‌های بتنی، سیم‌کشی و تاییدیه‌های بازرسی فنی.',
                'priority' => ProjectPriority::HIGH->value,
                'duration_days' => 45,
                'offset_days' => 0,
                'created_by' => $superAdmin->id,
            ]
        );

        ProjectTemplateRole::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'project_role' => ProjectMemberRole::PROJECT_MANAGER->value],
            [
                'cooperation_title' => 'مدیر اجرایی پروژه',
                'user_id' => $meterExpert->id,
            ]
        );

        ProjectTemplateRole::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'project_role' => ProjectMemberRole::PROJECT_SUPERVISOR->value],
            [
                'cooperation_title' => 'ناظر کیفی و تست عایقی',
                'user_id' => $inspector->id,
            ]
        );

        ProjectTemplateApprovalStage::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'stage_number' => 1],
            [
                'stage_name' => 'تایید طراحی و نقشه اجرایی توسط واحد مهندسی',
                'supervisor_user_id' => $engManager->id,
                'is_required' => true,
            ]
        );

        ProjectTemplateApprovalStage::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'stage_number' => 2],
            [
                'stage_name' => 'تایید نهایی آزمایش ترانس و برق‌دار کردن خط',
                'supervisor_user_id' => $opsManager->id,
                'is_required' => true,
            ]
        );

        $tTask1 = ProjectTemplateTask::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'title' => 'عملیات حفاری، فونداسیون و نصب پایه‌های بتنی ۱۲ متری'],
            [
                'description' => 'حفر گودال پایه‌ها و استقرار دکل‌ها با جرثقیل',
                'priority' => TaskPriority::HIGH->value,
                'offset_days' => 0,
                'duration_days' => 10,
                'executor_user_id' => $inspector->id,
                'version' => 'v1.0.0',
            ]
        );

        ProjectTemplateTask::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'parent_task_id' => $tTask1->id, 'title' => 'علامت‌گذاری و تراز فونداسیون طبق مختصات نقشه‌برداری'],
            [
                'description' => 'پایش شیب و عمق استاندارد چاله‌ها طبق استاندارد وزارت نیرو',
                'priority' => TaskPriority::MEDIUM->value,
                'offset_days' => 0,
                'duration_days' => 3,
                'executor_user_id' => $inspector->id,
                'version' => 'v1.0.0',
            ]
        );

        ProjectTemplateTask::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'parent_task_id' => $tTask1->id, 'title' => 'بتن‌ریزی پایه‌ها و عمل‌آوری عیار بتن'],
            [
                'description' => 'ریختن بتن با عیار ۳۵۰ و زمان‌بندی کیورینگ ۷ روزه',
                'priority' => TaskPriority::HIGH->value,
                'offset_days' => 3,
                'duration_days' => 7,
                'executor_user_id' => $inspector->id,
                'version' => 'v1.0.0',
            ]
        );

        $tTask2 = ProjectTemplateTask::firstOrCreate(
            ['project_template_id' => $sampleTemplate->id, 'title' => 'نصب سکوی ترانس، ترانسفورماتور ۲۰۰kVA و تابلوی توزیع'],
            [
                'description' => 'استقرار ترانسفورماتور و صاعقه‌گیر و سیستم ارتینگ استاندارد',
                'priority' => TaskPriority::URGENT->value,
                'offset_days' => 12,
                'duration_days' => 15,
                'executor_user_id' => $inspector->id,
                'version' => 'v1.0.0',
            ]
        );

        ProjectTemplateTaskSupervisor::firstOrCreate(
            ['project_template_task_id' => $tTask2->id, 'stage_number' => 1],
            [
                'stage_name' => 'تایید تست مقاومت اهمی چاه ارت و روغن ترانس',
                'user_id' => $inspector->id,
            ]
        );
    }
}
