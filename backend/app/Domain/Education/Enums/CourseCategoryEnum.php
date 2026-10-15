<?php

namespace App\Domain\Education\Enums;

enum CourseCategoryEnum: string
{
    case ONLINE_SAFETY = 'online_safety';
    case SCAM_AWARENESS = 'scam_awareness';
    case CYBERBULLYING_PREVENTION = 'cyberbullying_prevention';
    case HEALTHY_GAMING = 'healthy_gaming';
    case DIGITAL_LITERACY = 'digital_literacy';
    case AI_BASICS = 'ai_basics';
    case CODING = 'coding';
    case CONTENT_CREATION = 'content_creation';
    case ESPORTS_CAREERS = 'esports_careers';
    case DATA_SKILLS = 'data_skills';
    case DIGITAL_ENTREPRENEURSHIP = 'digital_entrepreneurship';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ONLINE_SAFETY => 'Online Safety',
            self::SCAM_AWARENESS => 'Scam Awareness & Anti-Phishing',
            self::CYBERBULLYING_PREVENTION => 'Cyberbullying Prevention',
            self::HEALTHY_GAMING => 'Healthy Gaming & Ergonomics',
            self::DIGITAL_LITERACY => 'Digital Literacy',
            self::AI_BASICS => 'AI Basics & Prompting',
            self::CODING => 'Coding & Game Development',
            self::CONTENT_CREATION => 'Streaming & Content Creation',
            self::ESPORTS_CAREERS => 'Esports Careers & Management',
            self::DATA_SKILLS => 'Data Skills & Analytics',
            self::DIGITAL_ENTREPRENEURSHIP => 'Digital Entrepreneurship',
        };
    }

    public function labelAm(): string
    {
        return match ($this) {
            self::ONLINE_SAFETY => 'የመስመር ላይ ደህንነት',
            self::SCAM_AWARENESS => 'የማጭበርበር ግንዛቤ',
            self::CYBERBULLYING_PREVENTION => 'የሳይበር ትንኮሳ መከላከል',
            self::HEALTHY_GAMING => 'ጤናማ የጨዋታ ልምዶች',
            self::DIGITAL_LITERACY => 'ዲጂታል ማንበብና መጻፍ',
            self::AI_BASICS => 'የአርቴፊሻል ኢንተለጀንስ መሰረቶች',
            self::CODING => 'ኮዲንግ እና የጨዋታ ልማት',
            self::CONTENT_CREATION => 'የይዘት ፈጠራ እና ስትሪሚንግ',
            self::ESPORTS_CAREERS => 'የኢስፖርት ሙያዎች',
            self::DATA_SKILLS => 'የዳታ ችሎታዎች',
            self::DIGITAL_ENTREPRENEURSHIP => 'ዲጂታል ስራ ፈጠራ',
        };
    }
}
