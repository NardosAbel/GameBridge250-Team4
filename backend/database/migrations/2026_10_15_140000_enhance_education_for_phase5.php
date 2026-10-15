<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance courses table
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'title_am')) {
                $table->string('title_am')->nullable()->after('title');
            }
            if (! Schema::hasColumn('courses', 'description_am')) {
                $table->text('description_am')->nullable()->after('description');
            }
            if (! Schema::hasColumn('courses', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('description_am');
            }
            if (! Schema::hasColumn('courses', 'status')) {
                $table->string('status', 20)->default('published')->after('is_published'); // draft, scheduled, published, archived
            }
            if (! Schema::hasColumn('courses', 'scheduled_for')) {
                $table->timestamp('scheduled_for')->nullable()->after('status');
            }
            if (! Schema::hasColumn('courses', 'language_support')) {
                $table->json('language_support')->nullable()->after('scheduled_for');
            }
            if (! Schema::hasColumn('courses', 'estimated_duration_minutes')) {
                $table->unsignedInteger('estimated_duration_minutes')->default(60)->after('language_support');
            }
            if (! Schema::hasColumn('courses', 'total_points')) {
                $table->unsignedInteger('total_points')->default(100)->after('estimated_duration_minutes');
            }
            if (! Schema::hasColumn('courses', 'is_offline_available')) {
                $table->boolean('is_offline_available')->default(true)->after('total_points');
            }
        });

        // 2. Create course_modules table
        if (! Schema::hasTable('course_modules')) {
            Schema::create('course_modules', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('title');
                $table->string('title_am')->nullable();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('order')->default(1);
                $table->timestamps();

                $table->index(['course_id', 'order']);
            });
        }

        // 3. Enhance lessons table
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'module_id')) {
                $table->foreignId('module_id')->nullable()->after('course_id')->constrained('course_modules')->nullOnDelete();
            }
            if (! Schema::hasColumn('lessons', 'title_am')) {
                $table->string('title_am')->nullable()->after('title');
            }
            if (! Schema::hasColumn('lessons', 'lesson_type')) {
                $table->string('lesson_type', 30)->default('article')->after('slug'); // article, video, pdf, quiz, interactive
            }
            if (! Schema::hasColumn('lessons', 'content_am')) {
                $table->longText('content_am')->nullable()->after('content');
            }
            if (! Schema::hasColumn('lessons', 'media_url')) {
                $table->string('media_url')->nullable()->after('content_am');
            }
            if (! Schema::hasColumn('lessons', 'video_embed_url')) {
                $table->string('video_embed_url')->nullable()->after('media_url');
            }
            if (! Schema::hasColumn('lessons', 'pdf_url')) {
                $table->string('pdf_url')->nullable()->after('video_embed_url');
            }
            if (! Schema::hasColumn('lessons', 'interactive_payload')) {
                $table->json('interactive_payload')->nullable()->after('pdf_url');
            }
            if (! Schema::hasColumn('lessons', 'is_preview')) {
                $table->boolean('is_preview')->default(false)->after('duration_minutes');
            }
            if (! Schema::hasColumn('lessons', 'is_downloadable')) {
                $table->boolean('is_downloadable')->default(true)->after('is_preview');
            }
            if (! Schema::hasColumn('lessons', 'offline_package')) {
                $table->json('offline_package')->nullable()->after('is_downloadable');
            }
        });

        // 4. Enhance quizzes table
        Schema::table('quizzes', function (Blueprint $table) {
            if (! Schema::hasColumn('quizzes', 'title_am')) {
                $table->string('title_am')->nullable()->after('title');
            }
            if (! Schema::hasColumn('quizzes', 'description')) {
                $table->text('description')->nullable()->after('title_am');
            }
            if (! Schema::hasColumn('quizzes', 'description_am')) {
                $table->text('description_am')->nullable()->after('description');
            }
            if (! Schema::hasColumn('quizzes', 'max_retries')) {
                $table->unsignedSmallInteger('max_retries')->default(3)->after('passing_score');
            }
            if (! Schema::hasColumn('quizzes', 'time_limit_minutes')) {
                $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('max_retries');
            }
            if (! Schema::hasColumn('quizzes', 'questions')) {
                $table->json('questions')->nullable()->after('time_limit_minutes');
            }
        });

        // 5. Enhance quiz_attempts table
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_attempts', 'answers')) {
                $table->json('answers')->nullable()->after('passed');
            }
            if (! Schema::hasColumn('quiz_attempts', 'attempt_number')) {
                $table->unsignedSmallInteger('attempt_number')->default(1)->after('answers');
            }
        });

        // 6. Enhance certificates table
        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'verification_code')) {
                $table->string('verification_code', 64)->nullable()->unique()->after('verification_hash');
            }
            if (! Schema::hasColumn('certificates', 'metadata')) {
                $table->json('metadata')->nullable()->after('verification_code');
            }
        });

        // 7. Create course_enrollments table
        if (! Schema::hasTable('course_enrollments')) {
            Schema::create('course_enrollments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('status', 20)->default('active'); // active, completed, dropped
                $table->unsignedSmallInteger('progress_percentage')->default(0);
                $table->foreignId('last_accessed_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // 8. Create lesson_progress table
        if (! Schema::hasTable('lesson_progress')) {
            Schema::create('lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
                $table->string('status', 20)->default('started'); // started, completed
                $table->unsignedInteger('last_position_seconds')->default(0);
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'lesson_id']);
                $table->index(['user_id', 'course_id', 'status']);
            });
        }

        // 9. Create learning_streaks table
        if (! Schema::hasTable('learning_streaks')) {
            Schema::create('learning_streaks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->unsignedSmallInteger('current_streak')->default(0);
                $table->unsignedSmallInteger('longest_streak')->default(0);
                $table->date('last_activity_date')->nullable();
                $table->unsignedInteger('total_lessons_completed')->default(0);
                $table->unsignedInteger('total_quizzes_passed')->default(0);
                $table->unsignedInteger('total_points')->default(0);
                $table->timestamps();
            });
        }

        // 10. Create learning_badges table
        if (! Schema::hasTable('learning_badges')) {
            Schema::create('learning_badges', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique();
                $table->string('name');
                $table->string('name_am')->nullable();
                $table->text('description')->nullable();
                $table->text('description_am')->nullable();
                $table->string('icon', 100)->nullable();
                $table->string('criteria_type', 50); // first_lesson, quiz_perfect, streak_7, course_complete, online_safety_master
                $table->unsignedInteger('criteria_threshold')->default(1);
                $table->timestamps();
            });
        }

        // 11. Create user_badges table
        if (! Schema::hasTable('user_badges')) {
            Schema::create('user_badges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('badge_id')->constrained('learning_badges')->cascadeOnDelete();
                $table->timestamp('awarded_at')->useCurrent();
                $table->timestamps();

                $table->unique(['user_id', 'badge_id']);
            });
        }

        // 12. Create learning_paths table
        if (! Schema::hasTable('learning_paths')) {
            Schema::create('learning_paths', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('title');
                $table->string('title_am')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->text('description_am')->nullable();
                $table->string('banner_url')->nullable();
                $table->string('institution_type', 50)->default('all'); // university, school, all
                $table->string('target_university')->nullable(); // e.g. "Addis Ababa University"
                $table->boolean('is_mandatory')->default(false);
                $table->timestamp('deadline')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_published')->default(true);
                $table->timestamps();

                $table->index(['target_university', 'is_published']);
            });
        }

        // 13. Create learning_path_courses table
        if (! Schema::hasTable('learning_path_courses')) {
            Schema::create('learning_path_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('learning_path_id')->constrained('learning_paths')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->unsignedSmallInteger('order')->default(1);
                $table->timestamps();

                $table->unique(['learning_path_id', 'course_id']);
                $table->index(['learning_path_id', 'order']);
            });
        }

        // 14. Create learning_path_assignments table
        if (! Schema::hasTable('learning_path_assignments')) {
            Schema::create('learning_path_assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('learning_path_id')->constrained('learning_paths')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by_id')->constrained('users')->cascadeOnDelete();
                $table->string('university_name')->nullable();
                $table->timestamp('due_date')->nullable();
                $table->string('status', 20)->default('assigned'); // assigned, in_progress, completed
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['university_name', 'status']);
            });
        }

        // 15. Create awareness_campaigns table
        if (! Schema::hasTable('awareness_campaigns')) {
            Schema::create('awareness_campaigns', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('title');
                $table->string('title_am')->nullable();
                $table->string('slug')->unique();
                $table->string('category', 60); // online_safety, scam_awareness, cyberbullying_prevention, healthy_gaming, etc.
                $table->text('description')->nullable();
                $table->text('description_am')->nullable();
                $table->string('banner_url')->nullable();
                $table->json('content_blocks')->nullable(); // array of articles, video embeds, infographic tips
                $table->json('shareable_assets')->nullable(); // posters, social card graphics, hashtags
                $table->foreignId('linked_quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
                $table->foreignId('linked_course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->string('status', 20)->default('published'); // draft, scheduled, published, completed
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->json('target_institutions')->nullable();
                $table->json('analytics')->nullable(); // views_count, shares_count, completions_count
                $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['category', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('awareness_campaigns');
        Schema::dropIfExists('learning_path_assignments');
        Schema::dropIfExists('learning_path_courses');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('learning_badges');
        Schema::dropIfExists('learning_streaks');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_modules');
    }
};
