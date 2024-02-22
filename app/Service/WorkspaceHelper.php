<?php


namespace App\Service;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class WorkspaceHelper
{

    protected $workspace;
    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    public function getRecords($relatedRecordIds = null)
    {
        return [];
    }


    public function getAllUsers()
    {
        $workspaceId = $this->workspace->id;
        $users = DB::table("user_workspace")->where("workspace_id", $workspaceId)->get();
        return $users;
    }
}
