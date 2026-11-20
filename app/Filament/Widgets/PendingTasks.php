<?php

namespace App\Filament\Widgets;

use App\Enums\JobPostingStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Settings\SettingResource;
use App\Models\Complaint;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * Lists what needs attention in the modules the current user is allowed to work on.
 */
class PendingTasks extends Widget
{
    protected string $view = 'filament.widgets.pending-tasks';

    protected static ?int $sort = 2;

    /**
     * @return array{tasks: list<array{label: string, hint: string, count: int, badge: string|null, url: string, icon: Heroicon, color: string}>}
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $overdueComplaints = $user->hasPermission('reclamaciones.view') ? Complaint::query()->overdue()->count() : 0;

        $candidates = [
            ['reclamaciones.view', fn (): int => Complaint::query()->open()->count(),
                'Reclamos por responder', $overdueComplaints > 0
                    ? "¡{$overdueComplaints} con el plazo legal de 30 días hábiles vencido! Respóndalos cuanto antes."
                    : 'Libro de Reclamaciones: plazo máximo de 30 días hábiles para responder.',
                ComplaintResource::getUrl(parameters: ['activeTab' => $overdueComplaints > 0 ? 'vencidos' : 'por-atender']), Heroicon::OutlinedBookOpen, $overdueComplaints > 0 ? 'danger' : 'warning'],
            ['mensajes.view', fn (): int => ContactMessage::query()->unread()->count(),
                'Mensajes sin leer', 'Consultas o sugerencias de la ciudadanía esperando respuesta.',
                ContactMessageResource::getUrl(parameters: ['activeTab' => 'sin-leer']), Heroicon::OutlinedEnvelope, 'warning'],
            ['noticias.publish', fn (): int => Post::query()->where('is_published', false)->count(),
                'Noticias en borrador', 'Revise las notas redactadas por el equipo y publíquelas cuando estén listas.',
                PostResource::getUrl(parameters: ['activeTab' => 'borradores']), Heroicon::OutlinedPencilSquare, 'info'],
            ['convocatorias.update', fn (): int => JobPosting::query()->where('status', JobPostingStatus::EnCurso)->whereDate('ends_at', '<', today())->count(),
                'Convocatorias con plazo vencido', 'Siguen «En curso» aunque su fecha de cierre ya pasó. Actualice el estado.',
                JobPostingResource::getUrl(parameters: ['activeTab' => 'vencidas']), Heroicon::OutlinedClock, 'danger'],
            ['convocatorias.update', fn (): int => JobPosting::query()->where('status', JobPostingStatus::EnCurso)->doesntHave('files')->count(),
                'Convocatorias en curso sin bases', 'Publique al menos las bases para que los postulantes puedan revisarlas.',
                JobPostingResource::getUrl(parameters: ['activeTab' => 'en-curso']), Heroicon::OutlinedDocumentMinus, 'warning'],
            ['documentos.view', fn (): int => Document::query()->visibleTo($user)->where('is_published', false)->count(),
                'Documentos sin publicar', 'Registrados pero ocultos al público (por ejemplo, sin archivo adjunto o pendientes de revisión).',
                DocumentResource::getUrl(parameters: ['activeTab' => 'sin-publicar']), Heroicon::OutlinedEyeSlash, 'gray'],
            ['noticias.update', fn (): int => Post::query()->published()->whereNull('cover_image')->count(),
                'Noticias sin imagen de portada', 'Se ven con un recuadro genérico en la portada y al compartir en redes.',
                PostResource::getUrl(parameters: ['activeTab' => 'sin-imagen']), Heroicon::OutlinedPhoto, 'gray'],
            ['noticias.view', fn (): int => Post::query()->where('is_published', true)->where('published_at', '>', now())->count(),
                'Noticias programadas', 'Se publicarán automáticamente en la fecha indicada.',
                PostResource::getUrl(parameters: ['activeTab' => 'programadas']), Heroicon::OutlinedCalendarDays, 'info'],
            ['unidades.update', fn (): int => OrganizationalUnit::query()->active()->whereNull('head_name')->count(),
                'Oficinas sin responsable', 'El directorio muestra «Por actualizar». Registre nombre y cargo.',
                OrganizationalUnitResource::getUrl(parameters: ['activeTab' => 'sin-responsable']), Heroicon::OutlinedUserMinus, 'gray'],
            ['datos_portal.update', fn (): int => Setting::query()->where(fn ($query) => $query->whereNull('value')->orWhere('value', ''))->count(),
                'Datos del portal sin configurar', 'Por ejemplo, enlaces a mesa de partes, libro de reclamaciones o transparencia.',
                SettingResource::getUrl(), Heroicon::OutlinedCog6Tooth, 'gray'],
        ];

        $tasks = [];

        if ($user->is_admin && blank($user->getAppAuthenticationSecret())) {
            $tasks[] = [
                'label' => 'Proteja su cuenta de administrador',
                'hint' => 'Active la verificación en dos pasos desde «Mi perfil» con una app como Google Authenticator.',
                'count' => 1,
                'badge' => 'Recomendado',
                'url' => filament()->getProfileUrl(),
                'icon' => Heroicon::OutlinedShieldExclamation,
                'color' => 'danger',
            ];
        }

        foreach ($candidates as [$permission, $counter, $label, $hint, $url, $icon, $color]) {
            if (! $user->hasPermission($permission)) {
                continue;
            }

            $count = $counter();

            if ($count > 0) {
                $badge = null;
                $tasks[] = compact('label', 'hint', 'count', 'badge', 'url', 'icon', 'color');
            }
        }

        return ['tasks' => $tasks];
    }
}
