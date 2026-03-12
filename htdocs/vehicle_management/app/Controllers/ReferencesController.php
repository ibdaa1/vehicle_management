<?php
/**
 * References Controller – departments, sections, divisions (public data for forms).
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Department;

class ReferencesController extends BaseController
{
    private Department $deptModel;

    public function __construct()
    {
        $this->deptModel = new Department();
    }

    /**
     * GET /api/v1/references
     * Returns all departments, sections, divisions – no auth required (for login/register forms).
     */
    public function index(Request $request, array $params = []): void
    {
        $refs = $this->deptModel->getAllReferences();
        Response::json([
            'success' => true,
            'data' => $refs,
        ]);
        return;
    }

    /**
     * GET /api/v1/references/departments
     */
    public function departments(Request $request, array $params = []): void
    {
        $departments = $this->deptModel->allDepartments();
        Response::json(['success' => true, 'data' => $departments]);
        return;
    }

    /**
     * GET /api/v1/references/sections/{departmentId}
     */
    public function sections(Request $request, array $params = []): void
    {
        $deptId = (int)($params['departmentId'] ?? 0);
        if ($deptId <= 0) {
            Response::error('Invalid department ID', 400);
            return;
        }
        $sections = $this->deptModel->getSections($deptId);
        Response::json(['success' => true, 'data' => $sections]);
        return;
    }

    /**
     * GET /api/v1/references/divisions/{sectionId}
     */
    public function divisions(Request $request, array $params = []): void
    {
        $secId = (int)($params['sectionId'] ?? 0);
        if ($secId <= 0) {
            Response::error('Invalid section ID', 400);
            return;
        }
        $divisions = $this->deptModel->getDivisions($secId);
        Response::json(['success' => true, 'data' => $divisions]);
        return;
    }
}
