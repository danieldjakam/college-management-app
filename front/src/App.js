import "bootstrap/dist/css/bootstrap.min.css";
import { useEffect, useState } from "react";
import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import "./App.css";
import "./styles/theme.css";

// Auth Components
import AppAuthProvider from "./components/AuthProvider";
import ProtectedRoute, {
  AccountantRoute,
  AdminRoute,
  PublicRoute,
  RoleBasedRedirect,
  TeacherRoute,
} from "./components/ProtectedRoute";
import { useAuth } from "./hooks/useAuth";
import { SchoolProvider } from "./contexts/SchoolContext";
import { ThemeProvider } from "./contexts/ThemeContext";

// Pages
import Error404 from "./pages/Error404";
import Levels from "./pages/Levels/Levels";
import Login from "./pages/Login";
import PaymentTranches from "./pages/PaymentTranches";
import SchoolClasses from "./pages/SchoolClasses/SchoolClasses";
import SchoolYears from "./pages/SchoolYears";
import Sections from "./pages/Sections/Sections";
import Settings from "./pages/Settings";
import UserProfile from "./pages/Profile/UserProfile";
import SeriesStudents from "./pages/Students/SeriesStudents";

// Comptable Pages
import ClassCompt from "./pages/comptables/Class";
import ParamsCompt from "./pages/comptables/Params";
import StudentsComp from "./pages/comptables/Students";
import StudentsByClass from "./pages/comptables/StudentsByClass";

// Payment Pages
import StudentPayment from "./pages/Payments/StudentPayment";
import PaymentReports from "./pages/Payments/PaymentReports";

// Reports
import Reports from "./pages/Reports";


// User Management
import UserManagement from "./pages/UserManagement";

// Subjects & Teachers
import Subjects from "./pages/Subjects/Subjects";
import SeriesSubjectConfiguration from "./pages/Subjects/SeriesSubjectConfiguration";
import Teachers from "./pages/Teachers/Teachers";
import TeacherAssignments from "./pages/Teachers/TeacherAssignments";
import TeacherAssignmentManagement from "./pages/Teachers/TeacherAssignmentManagement";

// Needs
import MyNeeds from "./pages/Needs/MyNeeds";
import NeedsManagement from "./pages/Needs/NeedsManagement";


// Components
import Sidebar from "./components/Sidebar";
import TopBar from "./components/TopBar";

// Composant interne qui utilise les hooks d'auth
const AppContent = () => {
  const { isAuthenticated, user } = useAuth();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const handleSidebarToggle = () => {
    setSidebarCollapsed(!sidebarCollapsed);
  };

  return (
    <div className="app">
      <Router>
        {isAuthenticated && (
          <Sidebar
            isCollapsed={sidebarCollapsed}
            onToggle={handleSidebarToggle}
          />
        )}

        <div
          className="main-content"
          style={{
            marginLeft: isAuthenticated
              ? sidebarCollapsed && !isMobile
                ? "80px"
                : "280px"
              : "0",
            transition: "margin-left 0.3s ease",
          }}
        >
          {isAuthenticated && (
            <TopBar
              onSidebarToggle={handleSidebarToggle}
              showSidebarToggle={isMobile}
            />
          )}

          <div className="view animate-fade-in">
            <Routes>
              {/* Route publique - Login */}
              <Route
                path="/login"
                element={
                  <PublicRoute redirectPath="/">
                    <Login />
                  </PublicRoute>
                }
              />

              {/* Route de redirection basée sur le rôle */}
              <Route
                path="/"
                element={
                  <RoleBasedRedirect>
                    <Sections />
                  </RoleBasedRedirect>
                }
              />

              {/* Routes principales - accessibles à tous les utilisateurs connectés */}
              <Route
                path="/profile"
                element={
                  <ProtectedRoute>
                    <UserProfile />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/students/series/:seriesId"
                element={
                  <ProtectedRoute>
                    <SeriesStudents />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/my-needs"
                element={
                  <ProtectedRoute>
                    <MyNeeds />
                  </ProtectedRoute>
                }
              />

              {/* Routes pour administrateurs uniquement */}
              <Route
                path="/sections"
                element={
                  <AdminRoute>
                    <Sections />
                  </AdminRoute>
                }
              />

              <Route
                path="/levels"
                element={
                  <AdminRoute>
                    <Levels />
                  </AdminRoute>
                }
              />

              <Route
                path="/school-classes"
                element={
                  <AdminRoute>
                    <SchoolClasses />
                  </AdminRoute>
                }
              />

              <Route
                path="/payment-tranches"
                element={
                  <AdminRoute>
                    <PaymentTranches />
                  </AdminRoute>
                }
              />

              <Route
                path="/settings"
                element={
                  <AdminRoute>
                    <Settings />
                  </AdminRoute>
                }
              />

              <Route
                path="/school-years"
                element={
                  <AdminRoute>
                    <SchoolYears />
                  </AdminRoute>
                }
              />

              <Route

                path="/user-management"
                element={
                  <AdminRoute>
                    <UserManagement />

                path="/subjects"
                element={
                  <AdminRoute>
                    <Subjects />
                  </AdminRoute>
                }
              />

              <Route
                path="/series-subject-configuration"
                element={
                  <AdminRoute>
                    <SeriesSubjectConfiguration />
                  </AdminRoute>
                }
              />

              <Route
                path="/teachers"
                element={
                  <AdminRoute>
                    <Teachers />
                  </AdminRoute>
                }
              />

              <Route
                path="/teacher-assignments"
                element={
                  <AdminRoute>
                    <TeacherAssignmentManagement />
                  </AdminRoute>
                }
              />

              <Route
                path="/teacher-assignments-old"
                element={
                  <AdminRoute>
                    <TeacherAssignments />
                  </AdminRoute>
                }
              />

              <Route
                path="/needs-management"
                element={
                  <AdminRoute>
                    <NeedsManagement />

                  </AdminRoute>
                }
              />

              {/* Routes pour comptables et administrateurs */}
              <Route
                path="/students-comp"
                element={
                  <AccountantRoute>
                    <StudentsComp />
                  </AccountantRoute>
                }
              />

              <Route
                path="/class-comp"
                element={
                  <AccountantRoute>
                    <ClassCompt />
                  </AccountantRoute>
                }
              />

              <Route
                path="/class-comp/:id"
                element={
                  <AccountantRoute>
                    <StudentsByClass />
                  </AccountantRoute>
                }
              />


              <Route
                path="/params-comp"
                element={
                  <AccountantRoute>
                    <ParamsCompt />
                  </AccountantRoute>
                }
              />

              <Route
                path="/payment-reports"
                element={
                  <AccountantRoute>
                    <PaymentReports />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports"
                element={
                  <AccountantRoute>
                    <Reports />
                  </AccountantRoute>
                }
              />

              <Route
                path="/student-payment/:studentId"
                element={
                  <AccountantRoute>
                    <StudentPayment />
                  </AccountantRoute>
                }
              />

              {/* 404 pour les utilisateurs connectés */}
              <Route
                path="*"
                element={
                  <ProtectedRoute>
                    <Error404 />
                  </ProtectedRoute>
                }
              />
            </Routes>
          </div>
        </div>
      </Router>
    </div>
  );
};

// Composant App principal avec provider
function App() {
  return (
    <AppAuthProvider>
      <SchoolProvider>
        <ThemeProvider>
          <AppContent />
        </ThemeProvider>
      </SchoolProvider>
    </AppAuthProvider>
  );
}

export default App;
