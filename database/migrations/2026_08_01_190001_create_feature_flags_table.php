<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loger's DB-driven feature-flag system. Two tables so admins can create
 * flags at runtime without a code deploy and can layer per-team/per-user
 * overrides on top of a global default.
 *
 * `feature_flags`  — the flag itself. Key is the code-facing identifier
 *                    (`trends-relationships`); enabled_by_default is the
 *                    baseline. rollout_percentage supports gradual rollout
 *                    (deterministic hash of scope id → bucket).
 *
 * `feature_flag_overrides` — polymorphic exceptions. A team or user gets
 *                            an explicit ON/OFF that beats the default and
 *                            the rollout roll. This is where admin toggles
 *                            individual customers on and off.
 *
 * Both tables are Auditable (owen-it/laravel-auditing) via the models so
 * every toggle leaves a trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique()->comment('code-facing identifier — kebab-case');
            $table->string('name')->comment('human display name for admin UI');
            $table->text('description')->nullable();
            $table->enum('scope', ['global', 'team', 'user'])->default('global')
                ->comment('who this flag is aimed at — governs what overrides make sense');
            $table->enum('category', ['experimental', 'gating', 'kill_switch', 'operations'])
                ->default('experimental')
                ->comment('classify for the admin UI — kill-switches get warnings');
            $table->boolean('enabled_by_default')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(0)
                ->comment('0-100 — hash-bucketed rollout on top of default. 0 = nobody, 100 = everybody');
            $table->json('metadata')->nullable()
                ->comment('freeform config per flag — e.g. allowlist emails, min_version, etc.');
            $table->timestamps();
            $table->softDeletes();

            $table->index('key');
            $table->index('scope');
        });

        Schema::create('feature_flag_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feature_flag_id')->constrained()->cascadeOnDelete();
            $table->morphs('scope'); // scope_type, scope_id — polymorphic to Team | User
            $table->boolean('enabled');
            $table->text('reason')->nullable()->comment('why this override exists — helps future audits');
            $table->timestamps();

            // A given flag can only have one override per scope entity.
            $table->unique(['feature_flag_id', 'scope_type', 'scope_id'], 'flag_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flag_overrides');
        Schema::dropIfExists('feature_flags');
    }
};
