<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubjectGroupsViewController extends Controller
{
    /**
     * Display the subject groups management page
     */
    public function index()
    {
        return view('admin.subject-groups');
    }
}
