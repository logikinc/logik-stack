<?php namespace BookStack\Http\Controllers;

use BookStack\Exceptions\PermissionsException;
use BookStack\Repos\PermissionsRepo;
use BookStack\Services\PermissionService;
use Illuminate\Http\Request;
use BookStack\Http\Requests;

class PermissionController extends Controller
{

    protected $permissionsRepo;

    /**
     * PermissionController constructor.
     * @param PermissionsRepo $permissionsRepo
     */
    public function __construct(PermissionsRepo $permissionsRepo)
    {
        $this->permissionsRepo = $permissionsRepo;
        parent::__construct();
    }

    /**
     * Show a listing of the roles in the system.
     */
    public function listRoles()
    {
        $this->checkPermission('user-roles-manage');
        $roles = $this->permissionsRepo->getAllRoles();
        return view('settings/roles/index', ['roles' => $roles]);
    }

    /**
     * Show the form to create a new role
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createRole()
    {
        $this->checkPermission('user-roles-manage');
        return view('settings/roles/create');
    }

    /**
     * Store a new role in the system.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function storeRole(Request $request)
    {
        $this->checkPermission('user-roles-manage');
        $this->validate($request, [
            'display_name' => 'required|min:3|max:200',
            'description' => 'max:250'
        ]);

        $this->permissionsRepo->saveNewRole($request->all());
        session()->flash('success', 'Role successfully created');
        return redirect('/settings/roles');
    }

    /**
     * Show the form for editing a user role.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws PermissionsException
     */
    public function editRole($id)
    {
        $this->checkPermission('user-roles-manage');
        $role = $this->permissionsRepo->getRoleById($id);
        if ($role->hidden) {
            throw new PermissionsException('This role cannot be edited');
        }
        return view('settings/roles/edit', ['role' => $role]);
    }

    /**
     * Updates a user role.
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function updateRole($id, Request $request)
    {
        $this->checkPermission('user-roles-manage');
        $this->validate($request, [
            'display_name' => 'required|min:3|max:200',
            'description' => 'max:250'
        ]);

        $this->permissionsRepo->updateRole($id, $request->all());
        session()->flash('success', 'Role successfully updated');
        return redirect('/settings/roles');
    }

    /**
     * Show the view to delete a role.
     * Offers the chance to migrate users.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showDeleteRole($id)
    {
        $this->checkPermission('user-roles-manage');
        $role = $this->permissionsRepo->getRoleById($id);
        $roles = $this->permissionsRepo->getAllRolesExcept($role);
        $blankRole = $role->newInstance(['display_name' => 'Don\'t migrate users']);
        $roles->prepend($blankRole);
        return view('settings/roles/delete', ['role' => $role, 'roles' => $roles]);
    }

    /**
     * Delete a role from the system,
     * Migrate from a previous role if set.
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function deleteRole($id, Request $request)
    {
        $this->checkPermission('user-roles-manage');

        try {
            $this->permissionsRepo->deleteRole($id, $request->get('migrate_role_id'));
        } catch (PermissionsException $e) {
            session()->flash('error', $e->getMessage());
            return redirect()->back();
        }

        session()->flash('success', 'Role successfully deleted');
        return redirect('/settings/roles');
    }
}
