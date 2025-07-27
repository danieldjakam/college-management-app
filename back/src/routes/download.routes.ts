const routerForDownload = require('express').Router();
const authForDownload = require('../middleware/auth');
const authAdminForDownload = require('../middleware/authAdmin');
const authGlobalForDownload = require('../middleware/auth_global');

const downloadController = require('../controllers/download.controller');

routerForDownload.get('/csv/students/:id', authGlobalForDownload, downloadController.downloadStudentsCsv);
routerForDownload.get('/pdf/students/:id/:array', authGlobalForDownload, downloadController.downloadStudentsPdf)
routerForDownload.get('/table/students/:id', authGlobalForDownload, downloadController.downloadTable)
routerForDownload.get('/pdf/insolvables/:id/:type/:payload', authGlobalForDownload, downloadController.downloadInsolvablesList);
routerForDownload.get('/pdf/bul5/:class_id/:student_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletin5)
routerForDownload.get('/pdf/bul4/:class_id/:student_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletin4)
routerForDownload.get('/pdf/bul2/:class_id/:student_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletin2)
routerForDownload.get('/pdf/bul1/:class_id/:student_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletin1)
routerForDownload.get('/pdf/bul/:class_id/:student_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletin)
routerForDownload.get('/pdf/bul/:class_id/:exam_id', authGlobalForDownload, downloadController.downloadBulletinByClass)
routerForDownload.get('/recu/:student_id/:amount/:diff/:payload', authGlobalForDownload, downloadController.downloadRecu);
routerForDownload.get('/recu2/:student_id/:amount/:diff/:payload', authGlobalForDownload, downloadController.downloadRecu2);
routerForDownload.get('/recette/:type/:date/:to', authGlobalForDownload, downloadController.downloadRecette);
routerForDownload.get('/etat', authGlobalForDownload, downloadController.etat);
routerForDownload.get('/recu/:recu_name', authGlobalForDownload, downloadController.getRecu);

routerForDownload.get('/note/:class_id/:section', authGlobalForDownload, downloadController.getCsvNoteImport);

module.exports = routerForDownload;