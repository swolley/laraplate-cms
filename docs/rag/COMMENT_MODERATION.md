# CMS — comment moderation and approval capture

## Purpose

CMS owns the **comment domain**: pending save capture, diff enrichment, publication after approval, and the **moderation adapter** registered on Core's `ModerationAdapterRegistry`.

CMS does **not** import AI config or jobs. Orchestration events are emitted by Core.

See also: `Modules/Core/docs/rag/EVENT_ORCHESTRATION.md`, `Modules/AI/docs/rag/MODERATION_AND_SEARCH.md`.

## Capabilities

| Concern | Owner | Artifact |
|---------|--------|----------|
| Pending comment save | CMS | `Comment` + `CommentApprovalCapture` |
| Modification diff | CMS | `CommentApprovalCapture::enrichDiff()` |
| Emit moderation need | Core | `ModificationRequiresModeration` on modification create |
| LLM context + prompts | CMS | `CommentModerationAdapter`, `CommentModerationPrompt` |
| AI analysis + vote | AI | `ApproveModificationJob` (via registry) |
| Publish approved comment | CMS | `Comment::applyModificationChanges()` |
| Post-approve translate | Core + AI | `ModificationApproved` |

## InternalFlow

1. User saves comment → `CommentApprovalCapture::captureSave()` creates active `Modification` with diff (`body`, `locale`, `content_id`, optional `rating_score`).
2. Core saves modification → `ModificationRequiresModeration` (first save, `wasRecentlyCreated`).
3. AI may run if `ai_moderation_comments` (setting `ai_moderation_{table}`) and global moderation enabled.
4. Comment hidden from public queries until approved.
5. Human approves in Filament → `applyModificationChanges()` → public comment + `ModificationApproved`.

`CommentApprovalCapture` does **not** dispatch events; Core emitter handles that.

## HowToUse — CommentModerationAdapter

**Path:** `Modules/CMS/app/Services/CommentModerationAdapter.php`

Implements `Modules\Core\Contracts\ModerationAdapter`:

- `supports()`: `modifiable_type === Comment::class`
- `build()`: loads article context from `content_id`; when `parent_id` is in the modification diff, loads the parent comment body for thread context; returns `ModerationRequest` with CMS-owned prompts (`CommentModerationPrompt`)

`CommentApprovalCapture::enrichDiff()` adds `parent_id` to the modification when the comment is a reply.

Registration in `CMSServiceProvider::boot()`:

```php
$this->app->make(ModerationAdapterRegistry::class)
    ->register($this->app->make(CommentModerationAdapter::class));
```

## Configuration

| Setting | Group | Effect |
|---------|-------|--------|
| `ai_moderation_comments` | `moderation` | Enables AI vote for comments (`HasApprovals::aiModerationEnabledBySettings()`) |
| `auto_translate_comments` | `translations` | Post-approve `TranslateModelJob` via `ModificationApproved` |

Seeded via Core settings; cache flushed on `Setting` save (`PerModelSettingResolver`).

## PermissionsAndSecurity

- Pending comments: not visible in public listings until modification approved.
- AI vote is additive; human approval still required per `approvers_required`.
- Audit trail in `approvals.meta` / `disapprovals.meta` (AI module writes JSON).

## ErrorsAndTroubleshooting

| Symptom | Check |
|---------|--------|
| Modification without `modifiable_id` on create | Expected for pending comments; builder uses diff JSON |
| AI never runs for comments | `ai_moderation_comments`, AI moderation enabled, builder registered |
| Comment stuck pending | Human approval in Filament, modification active |

## FAQPrompts

- How does CommentApprovalCapture work?
- When is ModificationRequiresModeration fired for comments?
- Where is CommentModerationContextBuilder registered?
- Does CMS call AI directly?
- What setting enables AI moderation for comments?
