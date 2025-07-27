const routerForUpload = require('express').Router();
const authForUplaod = require('../middleware/auth');
const authAdminForUplaod = require('../middleware/authAdmin');
const authGlobalForUplaod = require('../middleware/auth_global');

const uploadController = require('../controllers/uploadController');

routerForUpload.post('/students/csv/:id', authGlobalForUplaod, authAdminForUplaod, uploadController.uploadStudentCsv);
routerForUpload.post('/students/csv/modify/:id', authGlobalForUplaod, authAdminForUplaod, uploadController.uploadStudentModifyCsv);
routerForUpload.post('/teachers/csv', authGlobalForUplaod, authAdminForUplaod, uploadController.uploadTeacherCsv);
routerForUpload.post('/class/csv', authGlobalForUplaod, authAdminForUplaod, uploadController.uploadClassCsv);
routerForUpload.post('/notes/csv', authGlobalForUplaod, authAdminForUplaod, uploadController.uploadNoteCsv);

routerForUpload.post('/note/:exam_id/:class_id/:section', authGlobalForUplaod, authForUplaod, uploadController.uploadNote);

module.exports = routerForUpload;