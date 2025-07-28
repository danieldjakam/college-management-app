# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Structure

This is a college management system for "Groupe Scolaire Bilingue Privé La Semence" with three main components:

- **back/**: Node.js/TypeScript backend API server using Express (port 4000)
- **front/**: React frontend application (Create React App, port 3006)
- **cli/**: Command-line interface for administrative tasks
- **docs/**: PDF reports and documentation storage

## Development Commands

### Backend (back/)
```bash
cd back
npm run start          # Start development server with nodemon on port 4000
npm run create_school   # Initialize school database schema
```

### Frontend (front/)
```bash
cd front
npm start              # Start React dev server on port 3006
npm run build          # Build for production
npm test               # Run tests
```

### CLI (cli/)
```bash
cd cli
npm run createschool     # Create new school setup interactively
npm run createsuperuser  # Create admin user interactively
```

## Architecture Overview

### Backend Architecture
- **MVC Pattern**: Controllers handle business logic, routes define endpoints
- **Database**: MySQL (`sem` database) with mysql2 driver
- **Authentication**: JWT tokens with bcrypt password hashing, role-based middleware
- **File Handling**: PDF generation (pdf-creator-node), CSV parsing, file uploads with multer
- **Email**: Nodemailer for sending reports and notifications
- **Server**: Express with CORS enabled for cross-origin requests

### Key Backend Features
- **Multi-role Authentication**: Admin, teacher, accountant access levels
- **PDF Report Generation**: Bulletins, receipts, student lists, age tables, financial reports
- **Data Import/Export**: CSV handling for bulk student data operations
- **File Management**: Document storage in `docs/` with organized receipt and report generation

### Frontend Architecture
- **React 18**: Modern React with functional components and hooks
- **UI Framework**: Bootstrap 5 + Reactstrap + React Bootstrap Icons
- **Routing**: React Router v6 for SPA navigation
- **State Management**: Local state with API integration via axios
- **Internationalization**: French/English language support via `local/lang.js`
- **Responsive Design**: Mobile-first approach with custom CSS

### Database Schema (key tables)
- **Students**: Personal info, class assignments, fee tracking, academic records
- **Teachers**: Subject assignments, login credentials
- **Classes/Sections**: Academic structure organization
- **Payments**: Financial tracking with receipt generation (`payments_details`)
- **Grades**: Sequences, trimesters, annual exams by academic domain

### API Patterns
- RESTful endpoints organized by feature (`/students`, `/teachers`, `/classes`, etc.)
- Form validation with French error messages
- File upload handling for imports and document management
- PDF generation for various administrative documents

## Key Features
- **Academic Management**: Complete student lifecycle from enrollment to graduation
- **Financial Tracking**: Fee payments, receipt generation, financial reporting
- **Grade Management**: Sequence/trimester/annual exam tracking with bulletin generation
- **Multi-language**: French (primary) and English interface support
- **Document Generation**: Automated PDF reports for administrative tasks
- **Data Import**: CSV import for bulk student registration

## Environment Configuration
- Backend `.env` requires: `PORT`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`, `DB_HOST`, `SECRET`
- Database: MySQL server with `sem` database
- Frontend configured for port 3006, backend on port 4000
- File storage in `back/docs/` for generated documents