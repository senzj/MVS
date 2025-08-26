<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-gray-900">

        {{-- Nav Bar (sidebar, header) --}}
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            {{-- main nav group --}}
            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Shop')" class="grid">
                    
                    {{-- dashboard --}}
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>

                    {{-- orders --}}
                    <flux:navlist.item icon="shopping-cart" :href="route('orders')" :current="request()->routeIs('orders')" wire:navigate>{{ __('Orders') }}</flux:navlist.item>

                </flux:navlist.group>

                {{-- management nav group --}}
                <flux:navlist.group :heading="__('Management')" class="grid mt-2.5">
                    {{-- products --}}
                    <flux:navlist.item icon="shopping-bag" :href="route('products')" :current="request()->routeIs('products')" wire:navigate>{{ __('Products') }}</flux:navlist.item>

                    {{-- customers --}}
                    <flux:navlist.item icon="identification" :href="route('customers')" :current="request()->routeIs('customers')" wire:navigate>{{ __('Customers') }}</flux:navlist.item>

                    {{-- employees --}}
                    <flux:navlist.item icon="users" :href="route('employees')" :current="request()->routeIs('employees')" wire:navigate>{{ __('Employees') }}</flux:navlist.item>
                </flux:navlist.group>

                {{-- records nav group --}}
                <flux:navlist.group :heading="__('Records')" class="grid mt-2.5">
                    {{-- sales --}}
                    {{-- <flux:navlist.item icon="chart-bar" :href="route('sales')" :current="request()->routeIs('sales')" wire:navigate>{{ __('Sales') }}</flux:navlist.item> --}}

                    {{-- orders history --}}
                    <flux:navlist.item icon="clock" :href="route('orders.history')" :current="request()->routeIs('orders.history')" wire:navigate>{{ __('Orders History') }}</flux:navlist.item>

                    {{-- logs --}}
                    <flux:navlist.item icon="server" :href="route('logs')" :current="request()->routeIs('logs')" wire:navigate>{{ __('Logs') }}</flux:navlist.item>

                </flux:navlist.group>

            </flux:navlist>

            {{-- add space  --}}
            <flux:spacer />

            {{-- Desktop User Menu --}}
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate class="cursor-pointer">{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="cursor-pointer w-full hover:bg-red-500">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

        </flux:sidebar>

        {{-- Mobile User Menu --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        {{-- scripts --}}
        @include('components.scripts.toastrjs')

        @fluxScripts
    </body>
</html>

{{-- uses hero icons --}}