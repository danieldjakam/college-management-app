<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Get all teachers
     */
    public function getAllTeachers()
    {
        $teachers = Teacher::with(['schoolClass.section'])
                          ->orderBy('name')
                          ->get();
        return response()->json($teachers);
    }

    /**
     * Get one teacher by ID
     */
    public function getOneTeacher($id)
    {
        $teacher = Teacher::with(['schoolClass.section'])->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Enseignant non trouvé'
            ], 404);
        }

        return response()->json($teacher);
    }

    /**
     * Get teacher by token (authenticated user)
     */
    public function getTeacherOrAdmin(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 401);
        }

        return response()->json($user);
    }

    /**
     * Add new teacher
     */
    public function addTeacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'subname' => 'required|string|min:3|max:255',
            'sex' => 'required|in:f,m',
            'email' => 'sometimes|email|unique:teachers,email',
            'phone_number' => 'sometimes|string|max:20',
            'birthday' => 'sometimes|date',
            'birthday_place' => 'sometimes|string|max:255',
            'profession' => 'sometimes|string|max:255',
            'class_id' => 'sometimes|string|exists:class,id',
            'username' => 'required|string|unique:teachers,username',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $teacher = Teacher::create([
            'name' => $request->name,
            'subname' => $request->subname,
            'sex' => $request->sex,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'birthday' => $request->birthday,
            'birthday_place' => $request->birthday_place,
            'profession' => $request->profession,
            'class_id' => $request->class_id,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'school_id' => $request->school_id ?? 'GSBPL_001',
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant ajouté avec succès',
            'data' => $teacher->load(['schoolClass.section'])
        ], 201);
    }

    /**
     * Update teacher
     */
    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Enseignant non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:3|max:255',
            'subname' => 'sometimes|string|min:3|max:255',
            'sex' => 'sometimes|in:f,m',
            'email' => 'sometimes|email|unique:teachers,email,' . $id,
            'phone_number' => 'sometimes|string|max:20',
            'birthday' => 'sometimes|date',
            'birthday_place' => 'sometimes|string|max:255',
            'profession' => 'sometimes|string|max:255',
            'class_id' => 'sometimes|string|exists:class,id',
            'username' => 'sometimes|string|unique:teachers,username,' . $id,
            'password' => 'sometimes|string|min:6',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'subname', 'sex', 'email', 'phone_number',
            'birthday', 'birthday_place', 'profession', 'class_id',
            'username', 'status'
        ]);

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $teacher->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant mis à jour avec succès',
            'data' => $teacher->load(['schoolClass.section'])
        ]);
    }

    /**
     * Delete teacher
     */
    public function deleteTeacher($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Enseignant non trouvé'
            ], 404);
        }

        // Check if teacher has an assigned class
        if ($teacher->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un enseignant qui a une classe assignée'
            ], 400);
        }

        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enseignant supprimé avec succès'
        ]);
    }

    /**
     * Assign teacher to class
     */
    public function assignToClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|integer|exists:teachers,id',
            'class_id' => 'required|string|exists:class,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $teacher = Teacher::find($request->teacher_id);
        $teacher->class_id = $request->class_id;
        $teacher->save();

        return response()->json([
            'success' => true,
            'message' => 'Enseignant assigné à la classe avec succès',
            'data' => $teacher->load(['schoolClass.section'])
        ]);
    }

    /**
     * Get teachers without assigned class
     */
    public function getUnassignedTeachers()
    {
        $teachers = Teacher::whereNull('class_id')
                          ->orWhere('class_id', '')
                          ->orderBy('name')
                          ->get();
        return response()->json($teachers);
    }
}