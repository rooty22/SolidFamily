<!-- Interactive Simulator Section (Alpine.js) -->
<section id="<?= e($sec['id']) ?>" x-data="{
    shareValue: <?= (float)$shareValue ?>,
    foundingRatio: <?= (float)$foundingShareRatio ?>,
    maxLoanRatio: <?= (float)$maxLoanRatio ?>,
    currency: '<?= __('currency') ?>',
    shares: 2,
    loanMonths: 12,
    get monthlySub() { return this.shares * this.shareValue; },
    get foundingTotal() { return this.shares * this.foundingRatio; },
    get maxLoan() { return this.monthlySub * this.maxLoanRatio; },
    get monthlyInstallment() { return (this.maxLoan / this.loanMonths).toFixed(0); }
}" class="py-16 sm:py-20 lg:py-28 bg-slate-900 text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-extrabold uppercase tracking-widest text-gold-400 mb-2 block"><?= $t('kicker') ?></span>
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-4"><?= $t('title') ?></h2>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                <?= $tb('desc') ?>
            </p>
        </div>

        <div class="max-w-4xl mx-auto bg-slate-950/80 rounded-3xl p-6 sm:p-10 border border-slate-800 shadow-2xl backdrop-blur-xl">
            <div class="grid md:grid-cols-12 gap-8 items-center">
                <!-- Sliders Column -->
                <div class="md:col-span-7 space-y-6">
                    <!-- Shares Slider -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-bold text-slate-300"><?= __('sim_shares_label') ?></label>
                            <span class="text-xl font-black text-brand-400 font-num">
                                <span x-text="shares"></span> <?= is_rtl() ? 'أسهم' : 'shares' ?>
                            </span>
                        </div>
                        <input type="range" min="1" max="20" step="1" x-model.number="shares" class="w-full accent-brand-500 cursor-pointer h-2 bg-slate-800 rounded-lg">
                        <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                            <span>1</span>
                            <span>5</span>
                            <span>10</span>
                            <span>15</span>
                            <span>20</span>
                        </div>
                    </div>

                    <!-- Repayment Months Slider -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-bold text-slate-300"><?= __('sim_loan_months_label') ?></label>
                            <span class="text-xl font-black text-gold-400 font-num">
                                <span x-text="loanMonths"></span> <?= is_rtl() ? 'شهر' : 'months' ?>
                            </span>
                        </div>
                        <input type="range" min="3" max="36" step="1" x-model.number="loanMonths" class="w-full accent-gold-500 cursor-pointer h-2 bg-slate-800 rounded-lg">
                        <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                            <span>3</span>
                            <span>12</span>
                            <span>24</span>
                            <span>36</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-xs text-slate-400 leading-relaxed">
                        <i class="bi bi-info-circle-fill text-gold-400 me-1"></i>
                        <?= $tb('disclaimer') ?>
                    </div>
                </div>

                <!-- Live Results Card -->
                <div class="md:col-span-5 bg-gradient-to-b from-slate-900 to-slate-900/90 rounded-2xl p-6 border border-slate-800 space-y-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_monthly_sub') ?></div>
                        <div class="text-2xl font-black text-brand-400 font-num flex items-baseline gap-1.5">
                            <span x-text="monthlySub.toLocaleString()"></span>
                            <span class="text-xs font-bold text-brand-300" x-text="currency + '<?= is_rtl() ? '/شهر' : '/mo' ?>'"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_founding_total') ?></div>
                        <div class="text-2xl font-black text-sky-400 font-num flex items-baseline gap-1.5">
                            <span x-text="foundingTotal.toLocaleString()"></span>
                            <span class="text-xs font-bold text-sky-300" x-text="currency"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_max_loan') ?></div>
                        <div class="text-2xl sm:text-3xl font-black text-gold-400 font-num flex items-baseline gap-1.5">
                            <span x-text="maxLoan.toLocaleString()"></span>
                            <span class="text-xs font-bold text-gold-300" x-text="currency"></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <span><?= __('sim_installment') ?>:</span>
                            <b class="text-white font-num" x-text="monthlyInstallment + ' ' + currency + '<?= is_rtl() ? '/شهر' : '/mo' ?>'"></b>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a href="<?= $isLoggedIn ? url('loans/request') : url('login') ?>" class="w-full block text-center py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-sm shadow-glow transition-all">
                            <?= $isLoggedIn ? __('sim_apply_loan') : __('sim_login_to_apply') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
