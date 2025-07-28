import "bootstrap/dist/css/bootstrap.min.css";
import { useEffect, useState } from "react";
import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import "./App.css";

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

// Pages
import Error404 from "./pages/Error404";
import Levels from "./pages/Levels/Levels";
import Login from "./pages/Login";
import PaymentTranches from "./pages/PaymentTranches";
import SchoolClasses from "./pages/SchoolClasses/SchoolClasses";
import Sections from "./pages/Sections/Sections";
import Settings from "./pages/Settings";
import UserProfile from "./pages/Profile/UserProfile";

// Comptable Pages
import ClassCompt from "./pages/comptables/Class";
import ParamsCompt from "./pages/comptables/Params";
import StudentsComp from "./pages/comptables/Students";
import StudentsByClass from "./pages/comptables/StudentsByClass";

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
      <AppContent />
    </AppAuthProvider>
  );
}

export default App;
