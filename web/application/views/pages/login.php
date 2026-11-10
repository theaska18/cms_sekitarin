<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="page-hero relative isolate min-h-[calc(100vh-9.5rem)] overflow-hidden">
    <div class="pointer-events-none absolute -left-24 top-16 -z-10 h-72 w-72 rounded-full bg-cyanbright/10 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 bottom-0 -z-10 h-80 w-80 rounded-full bg-violetglow/15 blur-3xl" aria-hidden="true"></div>

    <div class="mx-auto flex w-full max-w-7xl justify-center px-5 py-12 sm:px-8 sm:py-20">
        <section class="w-full max-w-xl min-w-0 rounded-3xl border border-white/10 bg-panel/80 p-6 shadow-2xl shadow-black/20 backdrop-blur sm:p-10" aria-labelledby="login-title">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyanbright">CMSNivo workspace</p>
                <h1 id="login-title" class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Login</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">Enter your account details to preview the sign-in screen.</p>
            </div>

            <p id="login-status" class="mb-7 rounded-xl border border-amber-300/20 bg-amber-200/5 px-4 py-3 text-sm leading-6 text-amber-100" role="status" aria-live="polite">
                Login is not active yet. This preview does not send or save your information.
            </p>

            <form action="/login" method="get" autocomplete="off" aria-describedby="login-status" onsubmit="event.preventDefault(); return false;">
                <div class="space-y-5">
                    <div>
                        <label for="login-identifier" class="mb-2 block text-sm font-medium text-slate-200">Username or email</label>
                        <input id="login-identifier" type="text" autocomplete="off" class="min-h-[44px] w-full min-w-0 rounded-xl border border-white/15 bg-[#080d1b]/70 px-4 py-3 text-base text-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright" placeholder="Username or email">
                    </div>
                    <div>
                        <label for="login-password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                        <input id="login-password" type="password" autocomplete="off" class="min-h-[44px] w-full min-w-0 rounded-xl border border-white/15 bg-[#080d1b]/70 px-4 py-3 text-base text-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright" placeholder="Password">
                    </div>
                    <button type="button" class="mt-2 inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 px-5 py-3 font-semibold text-white shadow-glow transition hover:from-violet-500 hover:to-blue-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright focus-visible:ring-offset-2 focus-visible:ring-offset-[#111827]">
                        Login
                    </button>
                </div>
            </form>

            <p class="mt-6 text-center text-xs leading-5 text-slate-500">Prototype only — the fields are not used for authentication.</p>
        </section>
    </div>
</main>
