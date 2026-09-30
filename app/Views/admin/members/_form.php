<?php 
$m = $member ?? []; 
$isEn = is_en();
?>
<div class="space-y-6">
    <!-- Section 1: Personal & Identity Info -->
    <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80">
        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">1</span>
            <span><?= $isEn ? 'Personal & Identity Data' : 'البيانات الشخصية والهوية' ?></span>
        </h4>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Full Name' : 'الاسم الكامل' ?> <span class="text-rose-500">*</span></label>
                <input type="text" name="name" class="form-control text-sm" placeholder="<?= $isEn ? 'e.g. Mohammed Abdullah Al-Saleh' : 'مثال: محمد عبدالله الصالح' ?>" value="<?= e($m['name'] ?? old('name')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'National ID / Iqama' : 'رقم الهوية الوطنية / الإقامة' ?> <span class="text-rose-500">*</span></label>
                <input type="text" name="national_id" class="form-control text-sm font-numeric" placeholder="10xxxxxxxx" value="<?= e($m['national_id'] ?? old('national_id')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Mobile Phone' : 'رقم الجوال' ?> <span class="text-rose-500">*</span></label>
                <input type="text" name="mobile" class="form-control text-sm font-numeric" placeholder="05xxxxxxxx" value="<?= e($m['mobile'] ?? old('mobile')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Email Address' : 'البريد الإلكتروني' ?> <span class="text-rose-500">*</span></label>
                <input type="email" name="email" class="form-control text-sm" placeholder="user@domain.com" value="<?= e($m['email'] ?? old('email')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Date of Birth' : 'تاريخ الميلاد' ?></label>
                <input type="date" name="birth_date" class="form-control text-sm" value="<?= e($m['birth_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'National Address' : 'العنوان الوطني' ?></label>
                <input type="text" name="national_address" class="form-control text-sm" placeholder="<?= $isEn ? 'City, District, Street' : 'المدينة، الحي، اسم الشارع' ?>" value="<?= e($m['national_address'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Section 2: Banking Details -->
    <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80">
        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">2</span>
            <span><?= $isEn ? 'Banking Details' : 'البيانات البنكية وحساب الإيداع' ?></span>
        </h4>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Bank Name' : 'اسم البنك' ?></label>
                <input type="text" name="bank_name" class="form-control text-sm" placeholder="<?= $isEn ? 'e.g. Al Rajhi Bank' : 'مثال: مصرف الراجحي' ?>" value="<?= e($m['bank_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Account Number' : 'رقم الحساب البنكي' ?></label>
                <input type="text" name="bank_account_number" class="form-control text-sm font-numeric" placeholder="1234567890" value="<?= e($m['bank_account_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'IBAN' : 'رقم الآيبان (IBAN)' ?></label>
                <input type="text" name="iban" class="form-control text-sm font-numeric" placeholder="SAxxxxxxxxxxxxxxxxxxxxxxxx" value="<?= e($m['iban'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Section 3: Shares & Account Credentials -->
    <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80">
        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">3</span>
            <span><?= $isEn ? 'Shares & Security Credentials' : 'الأسهم وبيانات الدخول' ?></span>
        </h4>
        <div class="row g-3">
            <?php if (!isset($member)): ?>
                <div class="col-md-6">
                    <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Initial Shares Count' : 'عدد الأسهم التأسيسي' ?></label>
                    <input type="number" name="shares_count" class="form-control text-sm font-numeric font-bold" value="1" min="0">
                    <span class="text-[11px] text-slate-400 mt-1 block"><?= $isEn ? 'Can be adjusted later via shares manager' : 'يمكن تعديلها لاحقاً عبر شاشة إدارة الأسهم' ?></span>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= isset($member) ? ($isEn ? 'New Password (Leave blank to keep current)' : 'كلمة مرور جديدة (اتركه فارغاً للإبقاء على الحالية)') : ($isEn ? 'Account Password' : 'كلمة المرور') ?> <?= isset($member) ? '' : '<span class="text-rose-500">*</span>' ?></label>
                <input type="password" name="password" class="form-control text-sm" <?= isset($member) ? '' : 'required' ?> minlength="8" placeholder="••••••••">
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'This Member\'s Own Due Day' : 'يوم استحقاق خاص بهذا المشترك' ?></label>
                <input type="number" name="subscription_due_day" class="form-control text-sm font-numeric" min="1" max="28" placeholder="<?= $isEn ? 'System default' : 'افتراضي النظام' ?>" value="<?= e($m['subscription_due_day'] ?? '') ?>">
                <span class="text-[11px] text-slate-400 mt-1 block"><?= $isEn ? 'Leave blank to follow the system-wide default (Settings). Overrides it for every share of this member that has no due day of its own.' : 'اتركه فارغاً ليتبع الإعداد العام للنظام. تجاوزه يطبَّق على كل أسهم هذا المشترك التي ليس لها يوم استحقاق خاص بها.' ?></span>
            </div>
        </div>
    </div>

    <!-- Section 4: Administrative Role & Loan Exemption -->
    <div class="bg-amber-50/50 p-5 rounded-2xl border border-amber-200/80">
        <h4 class="text-xs font-black uppercase tracking-wider text-amber-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-bold">4</span>
            <span><?= $isEn ? 'Administrative Role & Loan Exemption' : 'الصفة الإدارية والإعفاء من القروض' ?></span>
        </h4>
        <div class="p-3.5 bg-white rounded-xl border border-amber-200/70">
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="is_admin" value="0">
                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="isAdminSwitch" name="is_admin" value="1" <?= !empty($m['is_admin']) ? 'checked' : '' ?>>
                <label class="form-check-label font-bold text-xs text-slate-800 cursor-pointer me-2" for="isAdminSwitch">
                    <?= $isEn ? 'Assign as Administrative Account (Exempt from Loans & Installments)' : 'تعيين كحساب إداري (معفى تماماً من القروض والأقساط التمويلية)' ?>
                </label>
            </div>
            <p class="text-[11px] text-slate-500 mt-1.5 mb-0 ms-1">
                <?= $isEn ? 'Admin accounts are dedicated to system/fund management. They are barred from taking loans, requesting financing, and will never show loan installments or loan debt.' : 'حسابات الإدارة مخصصة لإدارة الصندوق والإشراف عليه؛ ولا يمكنها الاقتراض أو طلب تمويل، ولا تظهر عليها أي أقساط أو ديون قروض.' ?>
            </p>
        </div>
    </div>
</div>
