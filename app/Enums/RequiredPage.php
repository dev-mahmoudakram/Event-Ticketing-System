<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The pages a payment gateway checks before approving the site. Every event has all six; they
 * can be edited but never deleted, renamed to another address or unpublished.
 */
enum RequiredPage: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Refund = 'refund';
    case Delivery = 'delivery';
    case About = 'about';
    case Contact = 'contact';

    public function slug(): string
    {
        return match ($this) {
            self::Terms => 'terms',
            self::Privacy => 'privacy-policy',
            self::Refund => 'refund-policy',
            self::Delivery => 'delivery-policy',
            self::About => 'about',
            self::Contact => 'contact',
        };
    }

    public function titleEn(): string
    {
        return match ($this) {
            self::Terms => 'Terms & Conditions',
            self::Privacy => 'Privacy Policy',
            self::Refund => 'Refund & Cancellation Policy',
            self::Delivery => 'Ticket Delivery Policy',
            self::About => 'About Us',
            self::Contact => 'Contact Us',
        };
    }

    public function titleAr(): string
    {
        return match ($this) {
            self::Terms => 'الشروط والأحكام',
            self::Privacy => 'سياسة الخصوصية',
            self::Refund => 'سياسة الاسترداد والإلغاء',
            self::Delivery => 'سياسة تسليم التذاكر',
            self::About => 'من نحن',
            self::Contact => 'تواصل معنا',
        };
    }

    public function sortOrder(): int
    {
        return (int) array_search($this, self::cases(), true);
    }
}
