<?php

namespace App\Concerns;

use Closure;
use Countable;
use Spatie\Navigation\Facades\Navigation;

class AppMenu implements Countable
{
    protected array $menu = [];

    /**
     * Make new menu.
     */
    public function make(string $name, Closure $callback): mixed
    {
        return $this->create($name, $callback);
    }

    /**
     * Create new menu.
     */
    public function create(string $name, Closure $resolver): mixed
    {
        $builder = Navigation::make();

        $this->menu[$name] = $builder;

        return $resolver($builder);
    }

    /**
     * Check if the menu exists.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->menu);
    }

    /**
     * Get instance of the given menu if exists.
     */
    public function instance(string $name): ?MenuBuilder
    {
        return $this->has($name) ? $this->menu[$name] : null;
    }

    /**
     * Modify a specific menu.
     */
    public function modify(string $name, Closure $callback): void
    {
        $menu = collect($this->menu)->filter(function ($menu) use ($name) {
            return $menu->getName() == $name;
        })->first();

        $callback($menu);
    }

    /**
     * Render the menu tag by given name.
     */
    public function get(string $name, string $presenter = null, array $bindings = []): ?array
    {
        return $this->has($name) ?
            $this->menu[$name]->tree() : null;
    }

    /**
     * Render the menu tag by given name.
     */
    public function render(string $name): ?array
    {
        return $this->get($name);
    }

    /**
     * Get a stylesheet for enable multilevel menu.
     */
    public function style(): mixed
    {
        return $this->views->make('menu::bootstrap3.style')->render();
    }

    /**
     * Get all menus.
     */
    public function all(): array
    {
        return $this->menu;
    }

    /**
     * Count menus.
     */
    public function count(): int
    {
        return count($this->menu);
    }

    /**
     * Empty the current menus.
     */
    public function destroy(): void
    {
        $this->menu = [];
    }
}
