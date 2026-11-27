<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\Workspace;
use App\Policies\WorkspacePolicy;
use App\Services\WorkspaceContext;
class AppServiceProvider extends ServiceProvider {
 public function register(): void {}
 public function boot(): void {
  Gate::policy(Workspace::class,WorkspacePolicy::class);
  Gate::policy(\App\Models\CalendarEvent::class,\App\Policies\CalendarEventPolicy::class);
  Gate::policy(\App\Models\PlanningTemplate::class,\App\Policies\PlanningTemplatePolicy::class);
  Gate::policy(\App\Models\Task::class,\App\Policies\TaskPolicy::class);
  View::composer('layouts.shell',function($view){
   if(!auth()->check())return;
   $context=app(WorkspaceContext::class);
   $view->with('shellWorkspaces',$context->available(auth()->user()))->with('shellCurrent',$context->current(request()));
  });
 }
}
