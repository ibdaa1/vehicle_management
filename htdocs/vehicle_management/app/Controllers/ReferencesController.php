<?php
/**
 * References Controller – sectors, departments, sections, divisions (public data for forms).
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Department;
use App\Models\Sector;

class ReferencesController extends BaseController
{
    private Department $deptModel;
    private Sector $sectorModel;

    public function __construct()
    {
        $this->deptModel = new Department();
        $this->sectorModel = new Sector();
    }

    /**
     * GET /api/v1/references
     * Returns all sectors, departments, sections, divisions – no auth required (for login/register forms).
     */
    public function index(Request $request, array $params = []): void
    {
        try {
            $refs = $this->deptModel->getAllReferences();
        } catch (\Throwable $e) {
            error_log("ReferencesController::index error: " . $e->getMessage());
            $refs = ['sectors' => [], 'departments' => [], 'sections' => [], 'divisions' => []];
        }
        Response::json([
            'success' => true,
            'data' => $refs,
        ]);
        return;
    }

    /**
     * GET /api/v1/references/sectors
     */
    public function sectors(Request $request, array $params = []): void
    {
        try {
            $sectors = $this->sectorModel->allSectors();
        } catch (\Throwable $e) {
            error_log("ReferencesController::sectors error: " . $e->getMessage());
            $sectors = [];
        }
        Response::json(['success' => true, 'data' => $sectors]);
        return;
    }

    /**
     * GET /api/v1/references/departments
     */
    public function departments(Request $request, array $params = []): void
    {
        try {
            $departments = $this->deptModel->allDepartments();
        } catch (\Throwable $e) {
            error_log("ReferencesController::departments error: " . $e->getMessage());
            $departments = [];
        }
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
        try {
            $sections = $this->deptModel->getSections($deptId);
        } catch (\Throwable $e) {
            error_log("ReferencesController::sections error: " . $e->getMessage());
            $sections = [];
        }
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
        try {
            $divisions = $this->deptModel->getDivisions($secId);
        } catch (\Throwable $e) {
            error_log("ReferencesController::divisions error: " . $e->getMessage());
            $divisions = [];
        }
        Response::json(['success' => true, 'data' => $divisions]);
        return;
    }
}
