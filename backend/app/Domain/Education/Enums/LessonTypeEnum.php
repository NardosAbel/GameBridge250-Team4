<?php

namespace App\Domain\Education\Enums;

enum LessonTypeEnum: string
{
    case ARTICLE = 'article';
    case VIDEO = 'video';
    case PDF = 'pdf';
    case QUIZ = 'quiz';
    case INTERACTIVE = 'interactive';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
