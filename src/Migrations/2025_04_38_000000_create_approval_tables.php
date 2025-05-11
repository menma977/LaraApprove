<?php

namespace Menma977\Larapprove\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Menma977\Larapprove\Helpers\ApprovalHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** approval flow */
        Schema::create('flows', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('flow_components', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignUlid('flow_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('model')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        /** approval flow */

        /** approval contributor group */
        Schema::create('groups', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_contributors', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignUlid('group_id')->constrained()->cascadeOnDelete();
            $table->string('contributor_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        /** approval contributor group */

        /** approval */
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignUlid('flow_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index()->default(ApprovalHelper::APPROVAL_TYPE_PARALLEL);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_statements', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_conditions', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_statement_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_components', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_statement_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level')->default(0);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color_code')->nullable();
            $table->boolean('can_drag')->default(true);
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_delete')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_contributors', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_component_id')->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->string('type')->index()->default(ApprovalHelper::CONTRIBUTOR_TYPE_AND);
            $table->json('conditions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        /** approval */

        /** approval event */
        Schema::create('approval_events', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->integer('level')->default(0);
            $table->foreignId('approval_id')->constrained()->cascadeOnDelete();
            $table->morphs('requestable');
            $table->string('type')->index()->default(ApprovalHelper::APPROVAL_TYPE_PARALLEL);
            $table->string('status')->index()->default(ApprovalHelper::APPROVE_EVENT_DRAFT);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('rollback_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_event_components', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_component_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level')->default(0);
            $table->string('type')->index()->default(ApprovalHelper::CONTRIBUTOR_TYPE_AND);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color_code')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('rollback_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_event_contributors', function (Blueprint $table) {
            $table->ulid('id')->primary()->index();
            $table->foreignId('approval_event_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_contributor_id')->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('rollback_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        /** approval event */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_event_contributors');
        Schema::dropIfExists('approval_event_components');
        Schema::dropIfExists('approval_events');
        Schema::dropIfExists('approval_contributors');
        Schema::dropIfExists('approval_components');
        Schema::dropIfExists('approval_conditions');
        Schema::dropIfExists('approval_statements');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('group_contributors');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('flow_components');
        Schema::dropIfExists('flows');
    }
};
