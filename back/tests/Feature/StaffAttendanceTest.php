<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $bibliothecaire;
    protected $teacher;
    protected $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les utilisateurs de test
        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_active' => true,
            'qr_code' => 'STAFF_1'
        ]);

        $this->bibliothecaire = User::factory()->create([
            'name' => 'Bibliothecaire Test', 
            'email' => 'biblio@test.com',
            'role' => 'bibliothecaire',
            'is_active' => true,
            'qr_code' => 'STAFF_2'
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com', 
            'role' => 'teacher',
            'is_active' => true,
            'qr_code' => 'STAFF_3'
        ]);

        // Créer une année scolaire
        $this->schoolYear = SchoolYear::factory()->create([
            'name' => '2023-2024',
            'is_current' => true
        ]);

        // Créer les paramètres d'école
        SchoolSetting::create([
            'school_name' => 'École Test',
            'primary_color' => '#1e40af'
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_scan_qr()
    {
        $response = $this->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->admin->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Non authentifié. Veuillez vous connecter.'
        ]);
    }

    /** @test */
    public function it_can_scan_qr_with_valid_authentication()
    {
        // Générer un token JWT pour le bibliothécaire
        $token = JWTAuth::fromUser($this->bibliothecaire);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Entrée enregistrée avec succès'
        ]);

        // Vérifier que l'attendance a été créée
        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $this->admin->id,
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry',
            'is_present' => true,
            'scanned_qr_code' => 'STAFF_1'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $token = JWTAuth::fromUser($this->bibliothecaire);

        // Test sans staff_qr_code
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'supervisor_id' => $this->bibliothecaire->id
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['staff_qr_code']);

        // Test sans supervisor_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supervisor_id']);
    }

    /** @test */
    public function it_handles_invalid_qr_code()
    {
        $token = JWTAuth::fromUser($this->bibliothecaire);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'INVALID_QR',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif'
        ]);
    }

    /** @test */
    public function it_prevents_rapid_successive_scans()
    {
        $token = JWTAuth::fromUser($this->bibliothecaire);

        // Premier scan
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(200);

        // Scan immédiat (dans les 5 secondes)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'exit'
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonStructure([
            'success',
            'message', 
            'time_remaining'
        ]);
    }

    /** @test */
    public function it_alternates_between_entry_and_exit()
    {
        $token = JWTAuth::fromUser($this->bibliothecaire);

        // Premier scan - doit être une entrée
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'auto'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.event_type', 'entry');

        // Attendre 6 secondes pour éviter la protection anti-spam
        sleep(6);

        // Deuxième scan - doit être une sortie
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'auto'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.event_type', 'exit');
    }

    /** @test */
    public function it_calculates_late_minutes_correctly()
    {
        $token = JWTAuth::fromUser($this->bibliothecaire);

        // Simuler une arrivée en retard (9h30)
        Carbon::setTestNow(Carbon::today()->setTime(9, 30, 0));

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.late_minutes', 60); // 1 heure de retard

        Carbon::setTestNow(); // Reset du temps
    }

    /** @test */
    public function unauthorized_role_cannot_scan()
    {
        // Créer un utilisateur avec un rôle non autorisé
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true
        ]);

        $token = JWTAuth::fromUser($student);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1', 
            'supervisor_id' => $student->id,
            'event_type' => 'entry'
        ]);

        // Devrait être rejeté par le middleware role
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_access_test_endpoints_without_auth()
    {
        $response = $this->postJson('/api/test/scan-qr-no-auth', [
            'staff_qr_code' => 'TEST_QR',
            'supervisor_id' => 1,
            'test_data' => 'Hello World'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Test endpoint accessible'
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'received_data',
                'timestamp',
                'server_ip'
            ]
        ]);
    }

    /** @test */
    public function it_can_access_debug_auth_endpoint_with_valid_token()
    {
        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/test/scan-qr-with-debug-auth', [
            'test_data' => 'Debug test'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Authentification JWT réussie'
        ]);
    }

    /** @test */
    public function it_provides_detailed_error_info_for_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_here'
        ])->postJson('/api/test/scan-qr-with-debug-auth', [
            'test_data' => 'Should fail'
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_code'
        ]);
    }

    /** @test */
    public function it_handles_missing_school_year()
    {
        // Supprimer l'année scolaire courante
        $this->schoolYear->update(['is_current' => false]);

        $token = JWTAuth::fromUser($this->bibliothecaire);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/staff-attendance/scan-qr', [
            'staff_qr_code' => 'STAFF_1',
            'supervisor_id' => $this->bibliothecaire->id,
            'event_type' => 'entry'
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Aucune année scolaire active trouvée'
        ]);
    }
}