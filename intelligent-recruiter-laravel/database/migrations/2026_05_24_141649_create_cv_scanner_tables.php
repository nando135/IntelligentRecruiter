<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('current_job_title')->nullable();
            $table->string('latest_company')->nullable();
            $table->text('professional_summary')->nullable();
            $table->integer('total_experience_months')->nullable();
            $table->decimal('total_experience_years', 5, 2)->nullable();
            $table->integer('internship_experience_months')->nullable();
            $table->integer('full_time_experience_months')->nullable();
            $table->string('resume_file')->nullable();
            $table->longText('raw_text')->nullable();
            $table->json('parsed_json')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->integer('duration_months')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('achievements')->nullable();
            $table->json('tools_used')->nullable();
            $table->string('industry')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('institution')->nullable();
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('start_year')->nullable();
            $table->string('end_year')->nullable();
            $table->string('cgpa')->nullable();
            $table->json('relevant_coursework')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('category')->nullable();
            $table->string('skill');
            $table->timestamps();
        });

        Schema::create('candidate_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('project_name')->nullable();
            $table->string('project_type')->nullable();
            $table->text('description')->nullable();
            $table->json('technologies')->nullable();
            $table->string('role')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('issuer')->nullable();
            $table->string('date_issued')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('credential_link')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('year')->nullable();
            $table->string('organization')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('language')->nullable();
            $table->string('proficiency')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('linkedin')->nullable();
            $table->string('github')->nullable();
            $table->string('portfolio')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_links');
        Schema::dropIfExists('candidate_languages');
        Schema::dropIfExists('candidate_achievements');
        Schema::dropIfExists('candidate_certifications');
        Schema::dropIfExists('candidate_projects');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidate_educations');
        Schema::dropIfExists('candidate_experiences');
        Schema::dropIfExists('candidates');
    }
};
