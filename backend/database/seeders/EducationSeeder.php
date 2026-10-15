<?php

namespace Database\Seeders;

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Enums\LessonTypeEnum;
use App\Domain\Education\Models\AwarenessCampaign;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseModule;
use App\Domain\Education\Models\LearningBadge;
use App\Domain\Education\Models\LearningPath;
use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        // 1. Seed Badges
        $badges = [
            [
                'slug' => 'first_lesson',
                'name' => 'First Step',
                'name_am' => 'የመጀመሪያ እርምጃ',
                'description' => 'Completed your very first digital awareness lesson.',
                'description_am' => 'የመጀመሪያውን የዲጂታል ግንዛቤ ትምህርት አጠናቀዋል።',
                'icon' => 'award',
                'criteria_type' => 'first_lesson',
            ],
            [
                'slug' => 'quiz_ace',
                'name' => 'Quiz Ace',
                'name_am' => 'የፈተና ጀግና',
                'description' => 'Scored 100% on a digital awareness quiz.',
                'description_am' => 'በዲጂታል ግንዛቤ ፈተና 100% ውጤት አስመዝግበዋል።',
                'icon' => 'check-circle-2',
                'criteria_type' => 'quiz_ace',
            ],
            [
                'slug' => 'streak_3',
                'name' => 'Consistent Scholar',
                'name_am' => 'ቀጣይነት ያለው ተማሪ',
                'description' => 'Maintained a 3-day continuous learning streak.',
                'description_am' => 'የ3 ቀን ተከታታይ የትምህርት ጉዞ አጠናቀዋል።',
                'icon' => 'zap',
                'criteria_type' => 'streak_3',
            ],
            [
                'slug' => 'cyber_shield',
                'name' => 'Cyber Shield',
                'name_am' => 'የሳይበር ጋሻ',
                'description' => 'Completed an online safety & cyber protection lesson.',
                'description_am' => 'የመስመር ላይ ደህንነት እና የሳይበር ጥበቃ ትምህርት አጠናቀዋል።',
                'icon' => 'shield-check',
                'criteria_type' => 'cyber_shield',
            ],
        ];

        foreach ($badges as $badge) {
            LearningBadge::firstOrCreate(['slug' => $badge['slug']], $badge);
        }

        if (! $admin) {
            return;
        }

        // 2. Seed Flagship Course: Online Safety & Anti-Scam in Ethiopian Gaming
        $safetyCourse = Course::firstOrCreate(
            ['slug' => 'online-safety-and-anti-scam-ethiopia'],
            [
                'title' => 'Online Safety, Scam Awareness & Account Security',
                'title_am' => 'የመስመር ላይ ደህንነት፣ የማጭበርበር ግንዛቤ እና የመለያ ጥበቃ',
                'category' => CourseCategoryEnum::ONLINE_SAFETY,
                'level' => CourseLevelEnum::BEGINNER,
                'instructor_id' => $admin->id,
                'description' => 'Learn how to protect your Steam, Discord, Telebirr, and gaming accounts from social engineering, phishing links, and account theft in Ethiopia.',
                'description_am' => 'የስቲም፣ የዲስኮርድ፣ የቴሌብር እና የጨዋታ መለያዎችን ከማህበራዊ ምህንድስና፣ ከአስመሳይ ሊንኮች እና ከስርቆት እንዴት መጠበቅ እንደሚቻል ይማሩ።',
                'cover_image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=800&q=80',
                'status' => CourseStatusEnum::PUBLISHED,
                'is_published' => true,
                'language_support' => ['en', 'am'],
                'estimated_duration_minutes' => 45,
                'total_points' => 100,
                'is_offline_available' => true,
            ]
        );

        $module1 = CourseModule::firstOrCreate(
            ['course_id' => $safetyCourse->id, 'order' => 1],
            [
                'title' => 'Recognizing Phishing & Telegram Scams',
                'title_am' => 'የማጭበርበሪያ ሊንኮችን እና የቴሌግራም ስካሞችን መለየት',
                'description' => 'Essential indicators of malicious bots, fake Telebirr screenshots, and tournament fee scams.',
            ]
        );

        $lesson1 = Lesson::firstOrCreate(
            ['course_id' => $safetyCourse->id, 'slug' => 'spotting-fake-tournament-links'],
            [
                'module_id' => $module1->id,
                'title' => 'Spotting Fake Tournament Registration Links',
                'title_am' => 'የውሸት የውድድር መመዝገቢያ ሊንኮችን መለየት',
                'lesson_type' => LessonTypeEnum::ARTICLE,
                'content' => "## How Phishing Targets Ethiopian Gamers\n\nScammers frequently create fake Telegram channels impersonating legitimate tournament organizers.\n\n### Warning Signs:\n1. Requests for your Steam or Google password to 'verify' your team.\n2. Telegram bots asking for your Telebirr PIN or SMS OTP.\n3. Unofficial payment screenshots without CBE / Telebirr transaction IDs.\n\n**Rule:** Legitimate organizers will NEVER ask for your password or OTP.",
                'content_am' => "## በኢትዮጵያ ውስጥ ተጫዋቾችን የሚያጠቁ የማጭበርበሪያ ዘዴዎች\n\nአጭበርባሪዎች ብዙውን ጊዜ እውነተኛ የውድድር አዘጋጆችን በማስመሰል የውሸት የቴሌግራም ቻናሎችን ይፈጥራሉ።\n\n### የማስጠንቀቂያ ምልክቶች:\n1. ቡድንዎን ለማረጋገጥ የስቲም ወይም የጉግል የይለፍ ቃልዎን መጠየቅ።\n2. የቴሌብር ሚስጥር ቁጥርዎን (PIN) ወይም የኤስኤምኤስ ኮድ (OTP) የሚጠይቁ ቦቶች።\n3. የኢትዮጵያ ንግድ ባንክ ወይም የቴሌብር የግብይት ቁጥር የሌላቸው የውሸት የክፍያ ደረሰኞች።\n\n**ዋና ህግ:** ህጋዊ አዘጋጆች መቼም ቢሆን የይለፍ ቃልዎን ወይም OTP አይጠይቁም።",
                'order' => 1,
                'duration_minutes' => 10,
                'is_preview' => true,
                'is_downloadable' => true,
            ]
        );

        $quiz1 = Quiz::firstOrCreate(
            ['course_id' => $safetyCourse->id, 'lesson_id' => $lesson1->id],
            [
                'title' => 'Phishing & Telegram Safety Check',
                'title_am' => 'የማጭበርበሪያ እና የቴሌግራም ደህንነት ፈተና',
                'passing_score' => 70,
                'max_retries' => 3,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'A tournament bot asks for your Telebirr SMS OTP to confirm your prize payout. What should you do?',
                        'question_am' => 'አንድ የውድድር ቦት የሽልማት ገንዘብዎን ለማረጋገጥ የቴሌብር SMS OTP እንዲልኩ ጠየቀዎት። ምን ማድረግ አለብዎት?',
                        'options' => [
                            'Share the OTP immediately so you get paid',
                            'Never share the OTP and report the bot',
                            'Share only the last 2 digits of the OTP',
                            'Forward the OTP to your team captain',
                        ],
                        'options_am' => [
                            'ገንዘቡ እንዲገባልኝ ወዲያውኑ OTP ኮዱን እልካለሁ',
                            'መቼም ቢሆን ኮዱን አላጋራም፤ ቦቱንም ሪፖርት አደርጋለሁ',
                            'የመጨረሻዎቹን 2 አሃዞች ብቻ አጋራለሁ',
                            'ኮዱን ለቡድን ካፒቴኔ አስተላልፋለሁ',
                        ],
                        'correct_index' => 1,
                        'explanation' => 'SMS OTPs give direct access to your financial accounts. Never share them under any circumstance.',
                        'explanation_am' => 'የኤስኤምኤስ OTP ለገንዘብ ሂሳብዎ ቀጥተኛ መዳረሻ ይሰጣል። በማንኛውም ሁኔታ ለማንም ማጋራት የለብዎትም።',
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'What is the primary indicator of a legitimate campus esports event in Ethiopia?',
                        'question_am' => 'በኢትዮጵያ ውስጥ ህጋዊ የካምፓስ ኢስፖርት ውድድር ዋና መለያ ምንድነው?',
                        'options' => [
                            'Verified organizer badge and platform-integrated payment/ticketing',
                            'An admin asking for login details in private DMs',
                            'A free tournament offering $10,000 with zero sponsors',
                            'A link redirecting to a suspicious URL shortener',
                        ],
                        'options_am' => [
                            'የተረጋገጠ የአዘጋጅ ባጅ እና በፕላትፎርሙ የተረጋገጠ ክፍያ/ትኬት',
                            'አድሚን በግል መልእክት የይለፍ ቃል ሲጠይቅ',
                            'ምንም ስፖንሰር ሳይኖረው 10,000 ዶላር ቃል የሚገባ ውድድር',
                            'ወደማይታወቅ አጠራጣሪ ሊንክ የሚያዞር አድራሻ',
                        ],
                        'correct_index' => 0,
                        'explanation' => 'Always verify official badge status and use official platform checkouts.',
                        'explanation_am' => 'ሁልጊዜ ኦፊሴላዊውን የተረጋገጠ ባጅ ያረጋግጡ እና በፕላትፎርሙ በኩል ብቻ ይገበያዩ።',
                    ],
                ],
            ]
        );

        // 3. Seed Learning Path: Addis Ababa University Freshman Cyber & Gaming Path
        $path = LearningPath::firstOrCreate(
            ['slug' => 'aau-freshman-digital-awareness'],
            [
                'title' => 'AAU Freshman Digital Awareness & Healthy Gaming Path',
                'title_am' => 'የአዲስ አበባ ዩኒቨርሲቲ አንደኛ አመት ተማሪዎች የዲጂታል ደህንነት እና ጤናማ ጌሚንግ ስርአተ ትምህርት',
                'target_university' => 'Addis Ababa University',
                'institution_type' => 'university',
                'is_mandatory' => true,
                'created_by_id' => $admin->id,
                'deadline' => now()->addMonths(2),
                'is_published' => true,
                'description' => 'Mandatory foundational digital awareness, ergonomics, and cyber safety for AAU university gaming club members.',
            ]
        );
        $path->courses()->sync([$safetyCourse->id => ['order' => 1]]);

        // 4. Seed Awareness Campaign: Safe Gaming Ethiopia 2026
        AwarenessCampaign::firstOrCreate(
            ['slug' => 'safe-gaming-ethiopia-2026'],
            [
                'title' => 'Safe Gaming Ethiopia 2026: Protect Your Identity',
                'title_am' => 'ደህንነቱ የተጠበቀ ጌሚንግ በኢትዮጵያ 2026፡ መለያዎን ይጠብቁ',
                'category' => CourseCategoryEnum::ONLINE_SAFETY->value,
                'description' => 'A nationwide initiative to eradicate esports scams, cyberbullying, and protect student gamer identities across Ethiopian universities.',
                'description_am' => 'በኢትዮጵያ ዩኒቨርሲቲዎች ውስጥ የኢስፖርት ማጭበርበሮችን እና የሳይበር ትንኮሳዎችን ለማጥፋት ያለመ ሀገር አቀፍ ዘመቻ።',
                'banner_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80',
                'linked_course_id' => $safetyCourse->id,
                'linked_quiz_id' => $quiz1->id,
                'status' => 'published',
                'created_by_id' => $admin->id,
                'target_institutions' => ['Addis Ababa University', 'AASTU', 'Hawassa University', 'Jimma University'],
                'content_blocks' => [
                    [
                        'type' => 'infographic_tips',
                        'title' => '3 Golden Rules of Campus Gaming',
                        'items' => [
                            'Never share your password or OTP with anyone',
                            'Enable Two-Factor Authentication on Steam and Discord',
                            'Report suspicious tournament fees immediately to university coordinators',
                        ],
                    ],
                ],
                'shareable_assets' => [
                    [
                        'type' => 'poster',
                        'title' => 'Anti-Scam Campus Poster',
                        'download_url' => '/posters/anti-scam-ethiopia.png',
                    ],
                ],
                'analytics' => [
                    'views_count' => 1420,
                    'shares_count' => 388,
                    'quiz_completions' => 612,
                ],
            ]
        );
    }
}
