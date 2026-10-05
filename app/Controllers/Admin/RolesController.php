<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Permissions\PermissionManager;
use PDO;

class RolesController extends Controller
{
    public function index(): void
    {
        $this->authorize('roles.manage');

        $roles = Role::allWithCounts();
        $adminMembers = PermissionManager::getAdministrativeMembers();

        // Fetch eligible non-admin members for quick assignment dropdown
        $eligibleMembers = Member::where(['status' => 'active'], 'name ASC');
        $existingAdminMemberIds = array_column($adminMembers, 'member_id');
        $eligibleMembers = array_filter($eligibleMembers, fn($m) => !in_array($m['id'], $existingAdminMemberIds, true));

        $this->view('admin/roles/index', [
            'pageTitle' => __('roles_and_permissions') ?? 'الأدوار والصلاحيات',
            'roles' => $roles,
            'adminMembers' => $adminMembers,
            'eligibleMembers' => $eligibleMembers,
            'groupedPermissions' => Permission::groupedByModule(),
        ]);
    }

    public function create(): void
    {
        $this->authorize('roles.manage');

        $this->view('admin/roles/create', [
            'pageTitle' => 'إنشاء دور وصلاحيات جديدة',
            'groupedPermissions' => Permission::groupedByModule(),
        ]);
    }

    public function store(): void
    {
        $this->authorize('roles.manage');
        $this->verifyCsrf();

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('name', 'رمز الدور (Slug)')
            ->required('label_ar', 'اسم الدور بالعربية')
            ->required('label_en', 'اسم الدور بالإنجليزية');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::setOld($data);
            $this->redirect('admin/roles/create');
        }

        $name = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $data['name'])));
        if (Role::findBy('name', $name)) {
            Session::flash('error', 'رمز الدور موجود مسبقاً، يرجى اختيار رمز آخر.');
            Session::setOld($data);
            $this->redirect('admin/roles/create');
        }

        $roleId = Role::create([
            'name' => $name,
            'label_ar' => trim((string) $data['label_ar']),
            'label_en' => trim((string) $data['label_en']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_system' => 0,
        ]);

        $selectedPermissions = $_POST['permissions'] ?? [];
        if (is_array($selectedPermissions)) {
            Role::syncPermissions($roleId, array_map('intval', $selectedPermissions));
        }

        PermissionManager::clearCache();
        Session::flash('success', 'تم إنشاء الدور وتعيين صلاحياته بنجاح.');
        $this->redirect('admin/roles');
    }

    public function edit(string $id): void
    {
        $this->authorize('roles.manage');

        $role = Role::find((int) $id);
        if (!$role) {
            Session::flash('error', 'الدور غير موجود.');
            $this->redirect('admin/roles');
        }

        $rolePerms = Role::getPermissions((int) $id);
        $rolePermIds = array_column($rolePerms, 'id');

        $this->view('admin/roles/edit', [
            'pageTitle' => 'تعديل الدور: ' . $role['label_ar'],
            'role' => $role,
            'rolePermIds' => $rolePermIds,
            'groupedPermissions' => Permission::groupedByModule(),
        ]);
    }

    public function update(string $id): void
    {
        $this->authorize('roles.manage');
        $this->verifyCsrf();

        $role = Role::find((int) $id);
        if (!$role) {
            Session::flash('error', 'الدور غير موجود.');
            $this->redirect('admin/roles');
        }

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('label_ar', 'اسم الدور بالعربية')
            ->required('label_en', 'اسم الدور بالإنجليزية');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("admin/roles/{$id}/edit");
        }

        Role::update((int) $id, [
            'label_ar' => trim((string) $data['label_ar']),
            'label_en' => trim((string) $data['label_en']),
            'description' => trim((string) ($data['description'] ?? '')),
        ]);

        // Don't modify super_admin permissions (must stay full access)
        if ($role['name'] !== 'super_admin') {
            $selectedPermissions = $_POST['permissions'] ?? [];
            if (is_array($selectedPermissions)) {
                Role::syncPermissions((int) $id, array_map('intval', $selectedPermissions));
            } else {
                Role::syncPermissions((int) $id, []);
            }
        }

        PermissionManager::clearCache();
        Session::flash('success', 'تم تحديث الدور وصلاحياته بنجاح.');
        $this->redirect('admin/roles');
    }

    public function destroy(string $id): void
    {
        $this->authorize('roles.manage');
        $this->verifyCsrf();

        $role = Role::find((int) $id);
        if (!$role) {
            Session::flash('error', 'الدور غير موجود.');
            $this->redirect('admin/roles');
        }

        if (!empty($role['is_system'])) {
            Session::flash('error', 'لا يمكن حذف الأدوار الأساسية للنظام.');
            $this->redirect('admin/roles');
        }

        Role::delete((int) $id);
        PermissionManager::clearCache();
        Session::flash('success', 'تم حذف الدور بنجاح.');
        $this->redirect('admin/roles');
    }

    public function showAssignMember(): void
    {
        $this->authorize('roles.manage');

        $memberId = (int) $this->input('member_id', 0);
        $member = $memberId ? Member::find($memberId) : null;
        $allMembers = Member::where(['status' => 'active'], 'name ASC');
        $roles = Role::all('is_system DESC, id ASC');

        // Check if member already has assigned role
        $currentRoleId = null;
        $currentDirectPerms = [];
        if ($member) {
            $db = Database::connection();
            $stmt = $db->prepare("
                SELECT ar.role_id FROM admin_roles ar
                INNER JOIN admins a ON a.id = ar.admin_id
                WHERE a.member_id = ? OR LOWER(a.email) = LOWER(?)
                LIMIT 1
            ");
            $stmt->execute([$member['id'], $member['email']]);
            $currentRoleId = $stmt->fetchColumn() ?: null;

            $stmt2 = $db->prepare("
                SELECT ap.permission_id FROM admin_permissions ap
                INNER JOIN admins a ON a.id = ap.admin_id
                WHERE a.member_id = ? OR LOWER(a.email) = LOWER(?)
            ");
            $stmt2->execute([$member['id'], $member['email']]);
            $currentDirectPerms = $stmt2->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $this->view('admin/roles/assign_member', [
            'pageTitle' => 'تعيين صلاحيات لوحة التحكم لعضو',
            'member' => $member,
            'allMembers' => $allMembers,
            'roles' => $roles,
            'currentRoleId' => $currentRoleId,
            'currentDirectPerms' => $currentDirectPerms,
            'groupedPermissions' => Permission::groupedByModule(),
        ]);
    }

    public function saveMemberAssignment(): void
    {
        $this->authorize('roles.manage');
        $this->verifyCsrf();

        $memberId = (int) $this->input('member_id', 0);
        $roleId = (int) $this->input('role_id', 0);
        $directPerms = $_POST['direct_permissions'] ?? [];

        if (!$memberId || !$roleId) {
            Session::flash('error', 'يرجى تحديد العضو والدور المطلوب تعيينه.');
            $this->redirect('admin/roles/assign');
        }

        try {
            PermissionManager::grantMemberDashboardAccess($memberId, $roleId, is_array($directPerms) ? array_map('intval', $directPerms) : []);
            Session::flash('success', 'تم تفعيل صلاحيات لوحة التحكم للعضو بنجاح.');
            $this->redirect('admin/roles');
        } catch (\Throwable $e) {
            Session::flash('error', 'حدث خطأ أثناء حفظ الصلاحيات: ' . $e->getMessage());
            $this->redirect('admin/roles/assign?member_id=' . $memberId);
        }
    }

    public function revokeMember(string $id): void
    {
        $this->authorize('roles.manage');
        $this->verifyCsrf();

        $memberId = (int) $id;
        try {
            PermissionManager::revokeMemberDashboardAccess($memberId);
            Session::flash('success', 'تم إلغاء صلاحيات لوحة التحكم وسحب الصفة الإدارية من العضو بنجاح.');
        } catch (\Throwable $e) {
            Session::flash('error', 'حدث خطأ أثناء إلغاء الصلاحيات: ' . $e->getMessage());
        }

        $this->redirect('admin/roles');
    }
}
