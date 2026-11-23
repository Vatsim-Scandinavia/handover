<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the client policy: the rules Handover enforces for each client beyond
 * OAuth itself. See ADR 0002 and CONTEXT.md.
 *
 * Every existing client gets a row that keeps today's behaviour: it may
 * request every scope, admits every member, and keeps one session per member.
 */
return new class extends Migration
{
    /** Every scope that existed before client policies. */
    private const array LEGACY_SCOPES = ['full_name', 'email', 'vatsim_details', 'country', 'groups'];

    public function up(): void
    {
        Schema::create('oauth_client_policies', function (Blueprint $table): void {
            $table->string('client_id', 36)->primary();
            $table->json('allowed_scopes');
            $table->boolean('single_session')->default(true);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
        });

        // The restrict on group_id makes the gate fail closed: a group can't
        // be deleted while a client requires it.
        Schema::create('oauth_client_required_groups', function (Blueprint $table): void {
            $table->string('client_id', 36);
            $table->uuid('group_id');
            $table->primary(['client_id', 'group_id']);
            $table->index('group_id');

            $table->foreign('client_id')->references('client_id')->on('oauth_client_policies')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->restrictOnDelete();
        });

        $now = now();
        DB::table('oauth_client_policies')->insert(DB::table('oauth_clients')->pluck('id')->map(fn ($clientId): array => [
            'client_id' => $clientId,
            'allowed_scopes' => json_encode(self::LEGACY_SCOPES),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_client_required_groups');
        Schema::dropIfExists('oauth_client_policies');
    }
};
