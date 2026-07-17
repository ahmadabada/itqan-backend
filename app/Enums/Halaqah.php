<?php

namespace App\Enums;

enum Halaqah: string
{
    case HamzaSayyam        = 'hamza_sayyam';
    case AbdulrahmanAbuJazar = 'abdulrahman_abu_jazar';
    case AbdullahRadwan     = 'abdullah_radwan';
    case AbdulmajeedAlbazm  = 'abdulmajeed_albazm';
    case FadiQazaar         = 'fadi_qazaar';
    case AlaaHanif          = 'alaa_hanif';
    case AhmadAbuAbada      = 'ahmad_abu_abada';

    public function label(): string
    {
        return match($this) {
            self::HamzaSayyam         => 'حمزة صيام',
            self::AbdulrahmanAbuJazar => 'عبد الرحمن أبو جزر',
            self::AbdullahRadwan      => 'عبدالله رضوان',
            self::AbdulmajeedAlbazm   => 'عبد المجيد البزم',
            self::FadiQazaar          => 'فادي قزاعر',
            self::AlaaHanif           => 'علاء حنيف',
            self::AhmadAbuAbada       => 'أحمد أبو عبادة',
        };
    }
}
