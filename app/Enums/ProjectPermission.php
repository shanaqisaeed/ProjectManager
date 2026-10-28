<?php

namespace App\Enums;

enum ProjectPermission: string
{
    case VIEW_PROJECT = 'VIEW_PROJECT';
    case UPDATE_PROJECT = 'UPDATE_PROJECT';
    case DELETE_PROJECT = 'DELETE_PROJECT';
    case ARCHIVE_PROJECT = 'ARCHIVE_PROJECT';
    case MANAGE_MEMBERS = 'MANAGE_MEMBERS';
    case INVITE_MEMBERS = 'INVITE_MEMBERS';
    case REMOVE_MEMBERS = 'REMOVE_MEMBERS';
    case CREATE_SUBPROJECT = 'CREATE_SUBPROJECT';
    case UPDATE_SUBPROJECT = 'UPDATE_SUBPROJECT';
    case DELETE_SUBPROJECT = 'DELETE_SUBPROJECT';
    case MANAGE_MILESTONES = 'MANAGE_MILESTONES';
    case CREATE_TASK = 'CREATE_TASK';
    case UPDATE_TASK = 'UPDATE_TASK';
    case DELETE_TASK = 'DELETE_TASK';
    case ASSIGN_TASK = 'ASSIGN_TASK';
    case VIEW_REPORTS = 'VIEW_REPORTS';

    // Prepared for future modules
    case MANAGE_GANTT = 'MANAGE_GANTT';
    case MANAGE_DEPENDENCIES = 'MANAGE_DEPENDENCIES';
    case EXPORT_PROJECT = 'EXPORT_PROJECT';
    case MANAGE_ATTACHMENTS = 'MANAGE_ATTACHMENTS';
    case MANAGE_COMMENTS = 'MANAGE_COMMENTS';

    // Template and Advanced modules
    case CREATE_TEMPLATE = 'CREATE_TEMPLATE';
    case MANAGE_TEMPLATES = 'MANAGE_TEMPLATES';
    case CREATE_FROM_TEMPLATE = 'CREATE_FROM_TEMPLATE';
    case MANAGE_APPROVAL_STAGES = 'MANAGE_APPROVAL_STAGES';
    case VIEW_ARCHIVED = 'VIEW_ARCHIVED';
    case MANAGE_TEAMS = 'MANAGE_TEAMS';

    public function label(): string
    {
        return match ($this) {
            self::VIEW_PROJECT => 'مشاهده پروژه',
            self::UPDATE_PROJECT => 'ویرایش پروژه',
            self::DELETE_PROJECT => 'حذف پروژه',
            self::ARCHIVE_PROJECT => 'آرشیو پروژه',
            self::MANAGE_MEMBERS => 'مدیریت اعضای پروژه',
            self::INVITE_MEMBERS => 'دعوت اعضا به پروژه',
            self::REMOVE_MEMBERS => 'حذف اعضا از پروژه',
            self::CREATE_SUBPROJECT => 'ایجاد زیرپروژه',
            self::UPDATE_SUBPROJECT => 'ویرایش زیرپروژه',
            self::DELETE_SUBPROJECT => 'حذف زیرپروژه',
            self::MANAGE_MILESTONES => 'مدیریت مایلستون‌ها',
            self::CREATE_TASK => 'ایجاد تسک',
            self::UPDATE_TASK => 'ویرایش تسک',
            self::DELETE_TASK => 'حذف تسک',
            self::ASSIGN_TASK => 'تخصیص تسک',
            self::VIEW_REPORTS => 'مشاهده گزارش‌ها',
            self::MANAGE_GANTT => 'مدیریت گانت چارت',
            self::MANAGE_DEPENDENCIES => 'مدیریت وابستگی‌ها',
            self::EXPORT_PROJECT => 'خروجی گرفتن از پروژه',
            self::MANAGE_ATTACHMENTS => 'مدیریت پیوست‌ها',
            self::MANAGE_COMMENTS => 'مدیریت نظرات',
            self::CREATE_TEMPLATE => 'ایجاد و ذخیره الگو از پروژه',
            self::MANAGE_TEMPLATES => 'مدیریت و ویرایش الگوهای پروژه',
            self::CREATE_FROM_TEMPLATE => 'ایجاد مستقیم پروژه از الگو',
            self::MANAGE_APPROVAL_STAGES => 'مدیریت ناظران و مراحل تایید',
            self::VIEW_ARCHIVED => 'مشاهده پروژه‌های آرشیو شده',
            self::MANAGE_TEAMS => 'مدیریت اکیپ‌ها و عناوین همکاری',
        };
    }
}
