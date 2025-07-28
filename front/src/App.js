import React, { useState, useEffect } from 'react'
import { Route, BrowserRouter as Router, Routes } from 'react-router-dom';
import './App.css';
import 'bootstrap/dist/css/bootstrap.min.css'

// Pages
import Class from './pages/Class/Class';
import Student from './pages/Students/Student';
import Teachers from './pages/Teachers/Teachers';
import Comp from './pages/Competences/Comp';
import Matiere from './pages/Matieres/Matiere';
import Login from './pages/Login';
import SearchView from './pages/Search';
import Params from './pages/Profile/Params';
import Error404 from './pages/Error404';
import TrimStu from './pages/Trimestres/TrimStu';
import SeqStu from './pages/Sequences/SeqStu';
import Settings from './pages/Settings';
import Home from './pages/Home';
import ClassBySection from './pages/Class/ClassBySection';
import SubComp from './pages/Competences/SubComp';
import Domains from './pages/Domains/Domains';
import Activities from './pages/Domains/Activities';
import Statistics from './pages/Statistics.jsx';
import Docs from './pages/Documentation';

// Notes & Bulletin Pages
import PrimEn from './pages/Notes/Exams/PrimEn';
import PrimFr from './pages/Notes/Exams/PrimFr';
import Cm2 from './pages/Notes/Exams/Cm2';
import PrimFrBE from './pages/Bulletin/Exams/PrimFr';
import PrimEnBE from './pages/Bulletin/Exams/PrimEn';
import Cm2BE from './pages/Bulletin/Exams/Cm2';
import MatEnT from './pages/Notes/Trimestres/MatEn';
import MatFrT from './pages/Notes/Trimestres/MatFr';
import PrimFrT from './pages/Notes/Trimestres/PrimFr';
import PrimEnT from './pages/Notes/Trimestres/PrimEn';
import Cm2T from './pages/Notes/Trimestres/Cm2';
import MatEnBT from './pages/Bulletin/Trimestres/MatEn';
import MatFrBT from './pages/Bulletin/Trimestres/MatFr';
import PrimFrBT from './pages/Bulletin/Trimestres/PrimFr';
import PrimEnBT from './pages/Bulletin/Trimestres/PrimEn';
import Cm2BT from './pages/Bulletin/Trimestres/Cm2';

// Comptable Pages
import StudentsComp from './pages/comptables/Students';
import ClassCompt from './pages/comptables/Class';
import ParamsCompt from './pages/comptables/Params';
import StudentsByClass from './pages/comptables/StudentsByClass';
import ReductFees from './pages/comptables/ReductFees';
import TransfertStudent from './pages/Students/TransfertStudent';

// Components
import Sidebar from './components/Sidebar';
import TopBar from './components/TopBar';

function App() {
  const [user, setUser] = useState(!!sessionStorage.user);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  useEffect(() => {
    // Update user state when sessionStorage changes
    const checkUser = () => {
      setUser(!!sessionStorage.user);
    };

    // Listen for storage changes
    window.addEventListener('storage', checkUser);
    return () => window.removeEventListener('storage', checkUser);
  }, []);

  const handleSidebarToggle = () => {
    setSidebarCollapsed(!sidebarCollapsed);
  };

  return (
    <div className="app">
      <Router>
        {user && (
          <Sidebar 
            isCollapsed={sidebarCollapsed} 
            onToggle={handleSidebarToggle}
          />
        )}
        
        <div className="main-content">
          {user && (
            <TopBar 
              user={user} 
              onSidebarToggle={handleSidebarToggle}
              showSidebarToggle={isMobile}
            />
          )}
          
          <div className="view animate-fade-in">
            <Routes>
              {user ? (
                <>
                  {/* Main Routes */}
                  <Route path='/' element={<Home/>} />
                  <Route path='/class' element={<Class/>} /> 
                  <Route path='/classBySection/:name' element={<ClassBySection />} /> 
                  <Route path='/students/:id' element={<Student/>} />
                  <Route path='/transfert/:id' element={<TransfertStudent/>} />
                  <Route path='/teachers' element={<Teachers/>} />
                  <Route path='/competences' element={<Comp/>} />
                  <Route path='/competences/:id' element={<SubComp/>}/>
                  <Route path='/matieres' element={<Matiere/>} /> 
                  <Route path='/domains' element={<Domains/>} /> 
                  <Route path='/domains/:id' element={<Activities/>} /> 
                  <Route path='/search' element={<SearchView/>}/>
                  <Route path='/params' element={<Params/>}/>
                  <Route path='/seqs' element={<SeqStu/>}/>
                  <Route path='/settings' element={<Settings/>}/>
                  <Route path='/trims' element={<TrimStu/>}/>
                  <Route path='/stats' element={<Statistics/>}/>
                  <Route path='/docs' element={<Docs />} />

                  {/* Comptable Routes */}
                  <Route path='/students-comp' element={<StudentsComp/>} />
                  <Route path='/class-comp' element={<ClassCompt/>} />
                  <Route path='/class-comp/:id' element={<StudentsByClass/>} />
                  <Route path='/reduct-fees/:id' element={<ReductFees/>} />
                  <Route path='/params-comp' element={<ParamsCompt/>} />

                  {/* Exam Routes */}
                  <Route path='/exams3/:exam_id/:class_id' element={<PrimFr type={3}/>} /> 
                  <Route path='/exams4/:exam_id/:class_id' element={<PrimEn type={4}/>} /> 
                  <Route path='/exams5/:exam_id/:class_id' element={<Cm2 type={5}/>} />

                  {/* Trimestre Routes */}
                  <Route path='/trims1/:exam_id/:class_id' element={<MatEnT type={1}/>} /> 
                  <Route path='/trims2/:exam_id/:class_id' element={<MatFrT type={2}/>} /> 
                  <Route path='/trims3/:exam_id/:class_id' element={<PrimFrT type={3}/>} /> 
                  <Route path='/trims4/:exam_id/:class_id' element={<PrimEnT type={4}/>} /> 
                  <Route path='/trims5/:exam_id/:class_id' element={<Cm2T type={5}/>} />

                  {/* Bulletin Exam Routes */}
                  <Route path='/exams3/:exam_id/:class_id/:student_id' element={<PrimFrBE type={3}/>} /> 
                  <Route path='/exams4/:exam_id/:class_id/:student_id' element={<PrimEnBE type={4}/>} /> 
                  <Route path='/exams5/:exam_id/:class_id/:student_id' element={<Cm2BE type={5}/>} />

                  {/* Bulletin Trimestre Routes */}
                  <Route path='/trims1/:exam_id/:class_id/:student_id' element={<MatEnBT type={1}/>} /> 
                  <Route path='/trims2/:exam_id/:class_id/:student_id' element={<MatFrBT type={2}/>} /> 
                  <Route path='/trims3/:exam_id/:class_id/:student_id' element={<PrimFrBT type={3}/>} /> 
                  <Route path='/trims4/:exam_id/:class_id/:student_id' element={<PrimEnBT type={4}/>} /> 
                  <Route path='/trims5/:exam_id/:class_id/:student_id' element={<Cm2BT type={5}/>}/>

                  {/* 404 */}
                  <Route path='*' element={<Error404/>} />
                </>
              ) : (
                <>
                  <Route path='/login' element={<Login setUser={setUser}/>} />
                  <Route path='*' element={<Login setUser={setUser} />} />
                </>
              )}
            </Routes>
          </div>
        </div>
      </Router>
    </div>
  );
}

export default App;