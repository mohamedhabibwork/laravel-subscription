<?php

namespace HSubscription\LaravelSubscription\Services;

use HSubscription\LaravelSubscription\Events\ModuleActivatedEvent;
use HSubscription\LaravelSubscription\Events\ModuleDeactivatedEvent;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\ModuleActivation;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModuleService
{
    public function hasAccess(Model $subscriber, Module|int|string $module, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $subscriber->subscription($subscriptionName);

        if (! $subscription || (! $subscription->isActive() && ! $subscription->isOnTrial())) {
            return false;
        }

        $module = $this->resolveModule($module);

        // Check if plan has this module enabled
        $planModule = $subscription->plan->modules()
            ->where('modules.id', $module->id)
            ->where('plan_module.is_enabled', true)
            ->first();

        if (! $planModule) {
            return false;
        }

        // Check if module is activated for this subscription
        return $subscription->moduleActivations()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->exists();
    }

    public function activate(Subscription $subscription, Module|int|string $module): ModuleActivation
    {
        return DB::transaction(function () use ($subscription, $module) {
            $module = $this->resolveModule($module);

            // Verify module is available in plan
            $planModule = $subscription->plan->modules()
                ->where('modules.id', $module->id)
                ->where('plan_module.is_enabled', true)
                ->first();

            if (! $planModule) {
                throw new \Exception("Module '{$module->name}' is not available in the current plan.");
            }

            $activation = $subscription->moduleActivations()
                ->where('module_id', $module->id)
                ->first();

            if ($activation) {
                $activation->update([
                    'is_active' => true,
                    'activated_at' => now(),
                    'deactivated_at' => null,
                ]);
            } else {
                $activation = $subscription->moduleActivations()->create([
                    'module_id' => $module->id,
                    'is_active' => true,
                    'activated_at' => now(),
                ]);
            }

            event(new ModuleActivatedEvent($subscription, $module, $activation));

            return $activation->fresh();
        });
    }

    public function deactivate(Subscription $subscription, Module|int|string $module): ModuleActivation
    {
        return DB::transaction(function () use ($subscription, $module) {
            $module = $this->resolveModule($module);

            $activation = $subscription->moduleActivations()
                ->where('module_id', $module->id)
                ->first();

            if (! $activation) {
                throw new \Exception("Module '{$module->name}' is not activated for this subscription.");
            }

            $activation->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            event(new ModuleDeactivatedEvent($subscription, $module, $activation));

            return $activation->fresh();
        });
    }

    public function getActiveModules(Subscription $subscription)
    {
        return $subscription->moduleActivations()
            ->where('is_active', true)
            ->with('module')
            ->get()
            ->pluck('module');
    }

    public function getModuleFeatures(Subscription $subscription, Module|int|string $module)
    {
        if (! $this->hasAccess($subscription->subscribable, $module)) {
            return collect();
        }

        $module = $this->resolveModule($module);

        return $module->features()->active()->get();
    }

    public function activatePlanModules(Subscription $subscription): void
    {
        $planModules = $subscription->plan->modules()
            ->where('plan_module.is_enabled', true)
            ->get();

        foreach ($planModules as $module) {
            $this->activate($subscription, $module);
        }
    }

    protected function resolveModule(Module|int|string $module): Module
    {
        if ($module instanceof Module) {
            return $module;
        }

        if (is_numeric($module)) {
            return Module::findOrFail($module);
        }

        return Module::where('slug', $module)->firstOrFail();
    }
}
