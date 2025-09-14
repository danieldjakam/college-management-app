import QRCode from "qrcode";
import { useEffect, useRef, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Col,
  Container,
  Form,
  Modal,
  Row,
  Spinner,
  Tab,
  Tabs,
} from "react-bootstrap";
import {
  Download,
  Eye,
  Gear,
  Image as ImageIcon,
  People,
  QrCode,
  Save,
  Upload,
} from "react-bootstrap-icons";
import { useAuth } from "../../hooks/useAuth";
import { secureApiEndpoints } from "../../utils/apiMigration";

// ========================================
// CONFIGURATION DES POSITIONS PAR DÉFAUT
// ========================================
// Positions optimisées pour le template PERSONNEL.png
const DEFAULT_POSITIONS = {
  // Position du QR Code - CENTRE DE LA CARTE POUR TEST
  QR: {
    x: 350, // Position horizontale - centre gauche pour être sûr de le voir
    y: 300, // Position verticale - centre pour être sûr de le voir
    size: 150, // Taille du QR code - TRÈS GRANDE pour être sûr de le voir
  },

  // Position de l'ID - CENTRE DROIT POUR TEST
  ID: {
    x: 130, // Position horizontale - plus à droite pour être sûr de le voir
    y: 230, // Position verticale - centre pour être sûr de le voir
    fontSize: 50, // Taille de la police - plus petite pour ne pas déborder
    color: "#7B2CBF", // Couleur VIOLETTE du logo - comme l'ancienne carte TCH_107
  },
};

// ========================================
// POUR MODIFIER LES POSITIONS :
// 1. Changez les valeurs ci-dessus
// 2. Sauvegardez le fichier
// 3. Rechargez la page
// ========================================

const CardGenerator = () => {
  const { user } = useAuth();
  const [template, setTemplate] = useState(null);
  const [templatePreview, setTemplatePreview] = useState(null);
  // Utiliser les positions par défaut définies en haut du fichier
  const [qrPosition, setQrPosition] = useState(() => ({
    x: DEFAULT_POSITIONS.QR.x,
    y: DEFAULT_POSITIONS.QR.y,
    size: DEFAULT_POSITIONS.QR.size,
  }));
  const [idPosition, setIdPosition] = useState(() => ({
    x: DEFAULT_POSITIONS.ID.x,
    y: DEFAULT_POSITIONS.ID.y,
    fontSize: DEFAULT_POSITIONS.ID.fontSize,
    color: DEFAULT_POSITIONS.ID.color,
  }));
  const [staffCategories, setStaffCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState("");
  const [staffList, setStaffList] = useState([]);
  const [selectedStaff, setSelectedStaff] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [previewCards, setPreviewCards] = useState([]);
  const [configStep, setConfigStep] = useState("template");

  // États pour la gestion des templates
  const [savedTemplates, setSavedTemplates] = useState([]);
  const [currentTemplateId, setCurrentTemplateId] = useState(null);
  const [templateName, setTemplateName] = useState("");
  const [showSaveTemplateModal, setShowSaveTemplateModal] = useState(false);
  const [isDefaultTemplate, setIsDefaultTemplate] = useState(false);

  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);

  // Catégories de personnel
  const categories = [
    {
      value: "all_staff",
      label: "🎯 TOUT LE PERSONNEL (203 personnes)",
      prefix: "STAF_",
      description: "Tous les utilisateurs système + tous les enseignants",
    },
    {
      value: "admin",
      label: "Personnel Administratif (82)",
      prefix: "STAF_",
      description: "Utilisateurs avec comptes système",
    },
    {
      value: "teacher_permanent",
      label: "Enseignants Permanents",
      prefix: "TCH_",
      description: "Enseignants avec comptes système",
    },
    {
      value: "teacher_semi",
      label: "Enseignants Semi-permanents",
      prefix: "TCH_",
      description: "Enseignants avec comptes système",
    },
    {
      value: "teacher_vacataire",
      label: "Enseignants Vacataires (121)",
      prefix: "TCH_",
      description: "Enseignants sans compte système",
    },
  ];

  // Fonction pour obtenir le préfixe selon la catégorie
  const getStaffPrefix = (categoryValue) => {
    const category = categories.find((cat) => cat.value === categoryValue);
    return category ? category.prefix : "STAF_";
  };

  // Charger TOUT le personnel (fusion users + teachers)
  const loadAllStaff = async () => {
    console.log("=== CHARGEMENT DE TOUT LE PERSONNEL ===");

    try {
      setLoading(true);
      let allStaff = [];

      // 1. Charger TOUS les utilisateurs système (82 personnes)
      console.log("📊 Chargement des utilisateurs système...");
      console.log("API endpoint utilisé:", secureApiEndpoints.users);

      let users = [];
      try {
        const usersResponse = await secureApiEndpoints.users.getAll();
        console.log("Response complète users:", usersResponse);
        users = usersResponse?.data || [];

        // Si la réponse est vide ou n'a pas la structure attendue, essayer la structure alternative
        if (users.length === 0 && usersResponse) {
          console.log("🔄 Tentative structure alternative...");
          // Parfois la data est directement dans la réponse
          users = Array.isArray(usersResponse) ? usersResponse : [];
          // Ou dans un autre champ
          if (users.length === 0 && usersResponse.users) {
            users = usersResponse.users;
          }
          console.log(`🔄 Structure alternative: ${users.length} utilisateurs`);
        }
      } catch (apiError) {
        console.error("❌ Erreur API users:", apiError);
        users = [];
      }

      console.log(`✅ ${users.length} utilisateurs système chargés`);

      if (users.length === 0) {
        console.warn("⚠️ PROBLÈME: Aucun utilisateur système trouvé!");
        console.warn("🔧 Cela peut être dû à:");
        console.warn("   - Permissions insuffisantes");
        console.warn("   - Endpoint API différent");
        console.warn("   - Structure de réponse différente");
      } else {
        console.log("👤 Exemple d'utilisateur:", users[0]);
      }

      // Transformer les users en format uniforme
      const usersFormatted = users.map((user) => ({
        id: user.id,
        name: user.name,
        email: user.email,
        contact: user.contact || user.phone,
        role: user.role,
        photo: user.photo,
        source: "users", // Identifier la source
        staff_id: `STAF_${String(user.id).padStart(3, "0")}`,
        qr_code: `STAF_${String(user.id).padStart(3, "0")}`,
        unique_id: `STAF_${String(user.id).padStart(3, "0")}`,
      }));

      allStaff = [...usersFormatted];

      // 2. Charger TOUS les enseignants (121 total dont 50 avec comptes et 71 sans comptes)
      console.log("📚 Chargement de tous les enseignants...");
      console.log("API endpoint utilisé:", secureApiEndpoints.teachers);
      const teachersResponse = await secureApiEndpoints.teachers.getAll();
      console.log("Response complète teachers:", teachersResponse);
      const teachers = teachersResponse?.data || [];
      console.log(`✅ ${teachers.length} enseignants chargés`);

      if (teachers.length === 0) {
        console.warn("⚠️ PROBLÈME: Aucun enseignant trouvé!");
      } else {
        console.log("👨‍🏫 Exemple d'enseignant:", teachers[0]);
      }

      // Transformer les teachers en format uniforme et éviter les doublons
      console.log("🔍 Filtrage des enseignants pour éviter doublons...");
      const teachersBeforeFilter = teachers.length;
      let ignoredCount = 0;
      const teachersFormatted = teachers
        .filter((teacher) => {
          // Exclure les enseignants qui ont déjà un compte utilisateur (éviter doublons)
          const hasUserAccount =
            teacher.user_id &&
            users.find((user) => user.id === teacher.user_id);
          if (hasUserAccount) {
            ignoredCount++;
            console.log(
              `🔄 Enseignant ${teacher.first_name} ${teacher.last_name} ignoré (a déjà un compte utilisateur ID: ${teacher.user_id})`
            );
          }
          return !hasUserAccount;
        })
        .map((teacher) => ({
          id: teacher.id,
          name: `${teacher.first_name} ${teacher.last_name}`,
          email: teacher.email,
          contact: teacher.phone_number,
          role: "teacher",
          photo: teacher.photo,
          source: "teachers", // Identifier la source
          staff_id:
            teacher.teacher_id || `TCH_${String(teacher.id).padStart(3, "0")}`,
          qr_code:
            teacher.teacher_id || `TCH_${String(teacher.id).padStart(3, "0")}`,
          unique_id:
            teacher.teacher_id || `TCH_${String(teacher.id).padStart(3, "0")}`,
          // Infos supplémentaires pour les enseignants
          first_name: teacher.first_name,
          last_name: teacher.last_name,
          type_personnel: teacher.type_personnel,
          qualification: teacher.qualification,
          specialization: teacher.specialization,
        }));

      allStaff = [...allStaff, ...teachersFormatted];

      console.log("=== RÉSUMÉ CHARGEMENT ===");
      console.log(`👥 Utilisateurs système: ${usersFormatted.length}`);
      console.log(`📚 Enseignants bruts: ${teachersBeforeFilter}`);
      console.log(`🔄 Enseignants avec comptes ignorés: ${ignoredCount}`);
      console.log(`👨‍🏫 Enseignants sans compte: ${teachersFormatted.length}`);
      console.log(`📊 TOTAL FINAL: ${allStaff.length} personnes`);
      console.log(
        `🎯 ATTENDU BD: 153 personnes (82 users + 71 teachers sans compte)`
      );
      console.log(`🔍 DIAGNOSTIC:`);
      console.log(`   - Si users API = 0 → Problème API users`);
      console.log(
        `   - Si enseignants ignorés ≠ 50 → Problème correspondances user_id`
      );
      console.log(`   - Si total ≠ 153 → Vérifiez les logs ci-dessus`);

      if (allStaff.length < 150) {
        console.warn("⚠️ WARNING: Moins de personnes que prévu chargées!");
        console.log("- Causes possibles:");
        console.log("  1. API users vide ou erreur");
        console.log("  2. API teachers ne retourne pas tous les enseignants");
        console.log("  3. Problème de correspondance user_id dans teachers");
      } else if (allStaff.length === 153) {
        console.log("✅ PARFAIT: Nombre exact selon vérification BD!");
      } else if (allStaff.length === 121) {
        console.warn("⚠️ PROBLÈME IDENTIFIÉ: API users probablement vide!");
        console.log(
          "→ Seuls les enseignants (121) sont chargés, pas les users système"
        );
      }
      console.log("=========================");

      setStaffList(allStaff);
    } catch (error) {
      console.error(
        "❌ Erreur lors du chargement du personnel complet:",
        error
      );

      // Solution de contournement : charger via les catégories existantes
      console.log("🔄 Tentative de chargement via catégories existantes...");
      try {
        // Charger les admins via la logique existante
        const adminResponse = await secureApiEndpoints.users.getAll();
        const adminUsers = adminResponse?.data || [];
        console.log(`🔄 Chargement admin: ${adminUsers.length} utilisateurs`);

        // Charger les enseignants vacataires
        const teachersResponse = await secureApiEndpoints.teachers.getAll();
        const allTeachers = teachersResponse?.data || [];
        const vacataireTeachers = allTeachers.filter(
          (t) => t.type_personnel === "V" || !t.user_id
        );
        console.log(
          `🔄 Chargement vacataires: ${vacataireTeachers.length} enseignants`
        );

        // Combiner en urgence
        const emergencyStaff = [
          ...adminUsers.map((user) => ({
            id: user.id,
            name: user.name,
            source: "users",
            staff_id: `STAF_${user.id}`,
            role: user.role,
          })),
          ...vacataireTeachers.map((teacher) => ({
            id: teacher.id,
            name: `${teacher.first_name} ${teacher.last_name}`,
            source: "teachers",
            staff_id: teacher.teacher_id || `TCH_${teacher.id}`,
            role: "teacher",
          })),
        ];

        console.log(
          `🔄 Chargement d'urgence: ${emergencyStaff.length} personnes`
        );
        setStaffList(emergencyStaff);
      } catch (fallbackError) {
        console.error("❌ Erreur même en mode dégradé:", fallbackError);
        setStaffList([]);
      }
    } finally {
      setLoading(false);
    }
  };

  // Charger la liste du personnel selon la catégorie
  const loadStaffByCategory = async (category) => {
    if (!category) return;

    // Si c'est "tout le personnel", utiliser la fonction spéciale
    if (category === "all_staff") {
      await loadAllStaff();
      return;
    }

    setLoading(true);
    try {
      let endpoint = "";
      let roleFilter = "";

      switch (category) {
        case "admin":
          roleFilter =
            "admin,secretaire,comptable_superieur,surveillant_general,surveillant_secteur";
          break;
        case "teacher_permanent":
          roleFilter = "teacher";
          endpoint = "teachers?type=permanent";
          break;
        case "teacher_semi":
          roleFilter = "teacher";
          endpoint = "teachers?type=semi_permanent";
          break;
        case "teacher_vacataire":
          roleFilter = "teacher";
          endpoint = "teachers?type=vacataire";
          break;
        default:
          return;
      }

      let response;
      let staffData = [];

      if (roleFilter === "admin") {
        // Pour les admins, utiliser l'endpoint users
        response = await secureApiEndpoints.users.getAll();
        console.log("Admin response:", response);
        staffData = response?.data || [];

        // Filtrer seulement les admins
        if (Array.isArray(staffData)) {
          staffData = staffData.filter(
            (user) =>
              user.roles?.some((role) =>
                [
                  "admin",
                  "secretaire",
                  "comptable_superieur",
                  "surveillant_general",
                  "surveillant_secteur",
                ].includes(role.name)
              ) || user.role === "admin"
          );
        }
      } else {
        // Pour les enseignants, utiliser le bon endpoint
        response = await secureApiEndpoints.teachers.getAll();
        console.log("Teachers response:", response);
        staffData = response?.data || [];

        // Filtrer par type d'enseignant
        if (Array.isArray(staffData) && endpoint) {
          if (endpoint.includes("permanent")) {
            staffData = staffData.filter((t) => t.type_personnel === "P");
          } else if (endpoint.includes("semi_permanent")) {
            staffData = staffData.filter((t) => t.type_personnel === "S");
          } else if (endpoint.includes("vacataire")) {
            staffData = staffData.filter((t) => t.type_personnel === "V");
          }
        }
      }

      console.log("Final staff data:", staffData);
      setStaffList(Array.isArray(staffData) ? staffData : []);
    } catch (error) {
      console.error("Erreur lors du chargement du personnel:", error);
      setStaffList([]);
    }
    setLoading(false);
  };

  // Charger les templates sauvegardés (positions seulement)
  const loadSavedTemplates = async () => {
    try {
      const savedTemplatesData = localStorage.getItem("cardTemplates");
      const templates = savedTemplatesData
        ? JSON.parse(savedTemplatesData)
        : [];
      setSavedTemplates(templates);
    } catch (error) {
      console.error("Erreur lors du chargement des templates:", error);
      setSavedTemplates([]);
    }
  };

  // Charger un template spécifique (positions seulement)
  const loadTemplate = async (templateData) => {
    try {
      setCurrentTemplateId(templateData.id);
      setTemplateName(templateData.name);

      // Charger les positions (utiliser les valeurs par défaut du code source si pas définies)
      setQrPosition({
        x: parseInt(templateData.qr_x) || DEFAULT_POSITIONS.QR.x,
        y: parseInt(templateData.qr_y) || DEFAULT_POSITIONS.QR.y,
        size: parseInt(templateData.qr_size) || DEFAULT_POSITIONS.QR.size,
      });

      setIdPosition({
        x: parseInt(templateData.id_x) || DEFAULT_POSITIONS.ID.x,
        y: parseInt(templateData.id_y) || DEFAULT_POSITIONS.ID.y,
        fontSize:
          parseInt(templateData.id_font_size) || DEFAULT_POSITIONS.ID.fontSize,
        color: templateData.id_color || DEFAULT_POSITIONS.ID.color,
      });

      // Note: L'image du template doit être rechargée manuellement
      // car elle n'est plus stockée pour éviter les erreurs de quota
      alert(
        `Template "${templateData.name}" chargé avec succès !\n\nVeuillez recharger votre image template PERSONNEL.png si nécessaire.`
      );
    } catch (error) {
      console.error("Erreur lors du chargement du template:", error);
    }
  };

  // Sauvegarder le template actuel (temporairement dans localStorage)
  const saveCurrentTemplate = async () => {
    if (!templateName.trim()) {
      alert("Veuillez saisir un nom pour le template");
      return;
    }

    try {
      setLoading(true);

      // Récupérer les templates existants
      const existingTemplates = JSON.parse(
        localStorage.getItem("cardTemplates") || "[]"
      );

      // Créer l'objet template SANS l'image pour éviter QuotaExceededError
      const templateData = {
        id: currentTemplateId || Date.now().toString(),
        name: templateName,
        // template_preview: templatePreview, // SUPPRIMÉ pour éviter quota exceeded
        qr_x: qrPosition.x,
        qr_y: qrPosition.y,
        qr_size: qrPosition.size,
        id_x: idPosition.x,
        id_y: idPosition.y,
        id_font_size: idPosition.fontSize,
        id_color: idPosition.color,
        is_default: isDefaultTemplate,
        created_at: new Date().toISOString(),
      };

      let updatedTemplates;
      if (currentTemplateId) {
        // Mise à jour d'un template existant
        updatedTemplates = existingTemplates.map((t) =>
          t.id === currentTemplateId ? templateData : t
        );
      } else {
        // Nouveau template
        updatedTemplates = [...existingTemplates, templateData];
        setCurrentTemplateId(templateData.id);
      }

      // Si c'est le nouveau template par défaut, désactiver les autres
      if (isDefaultTemplate) {
        updatedTemplates = updatedTemplates.map((t) => ({
          ...t,
          is_default: t.id === templateData.id,
        }));
      }

      // Sauvegarder dans localStorage
      localStorage.setItem("cardTemplates", JSON.stringify(updatedTemplates));

      setShowSaveTemplateModal(false);
      loadSavedTemplates(); // Recharger la liste

      // Notification de succès
      alert("Template sauvegardé avec succès !");
    } catch (error) {
      console.error("Erreur lors de la sauvegarde du template:", error);
      alert("Erreur lors de la sauvegarde du template");
    }
    setLoading(false);
  };

  // Supprimer un template (temporairement depuis localStorage)
  const deleteTemplate = async (templateId) => {
    if (!window.confirm("Êtes-vous sûr de vouloir supprimer ce template ?")) {
      return;
    }

    try {
      setLoading(true);

      // Récupérer les templates existants et supprimer celui avec l'ID donné
      const existingTemplates = JSON.parse(
        localStorage.getItem("cardTemplates") || "[]"
      );
      const updatedTemplates = existingTemplates.filter(
        (t) => t.id !== templateId
      );

      // Sauvegarder dans localStorage
      localStorage.setItem("cardTemplates", JSON.stringify(updatedTemplates));

      loadSavedTemplates(); // Recharger la liste

      // Si c'était le template actuel, le réinitialiser
      if (currentTemplateId === templateId) {
        setCurrentTemplateId(null);
        setTemplateName("");
      }

      alert("Template supprimé avec succès !");
    } catch (error) {
      console.error("Erreur lors de la suppression du template:", error);
      alert("Erreur lors de la suppression du template");
    }
    setLoading(false);
  };

  // Restaurer le template sauvegardé au chargement
  const restoreSavedTemplate = () => {
    try {
      const savedTemplate = localStorage.getItem("currentTemplate");
      console.log("🔍 Vérification template sauvegardé:", !!savedTemplate);
      if (savedTemplate) {
        console.log(
          "📁 Template trouvé, taille:",
          savedTemplate.length,
          "chars"
        );
        setTemplate(savedTemplate);
        setTemplatePreview(savedTemplate);
        console.log("✅ Template restauré depuis le stockage");
        // Confirmer que les états sont mis à jour
        console.log("State template mis à jour:", !!savedTemplate);
        console.log("State templatePreview mis à jour:", !!savedTemplate);
      } else {
        console.log("❌ Aucun template sauvegardé trouvé");
      }
    } catch (error) {
      console.error("Erreur lors de la restauration du template:", error);
    }
  };

  // Utiliser DEFAULT_POSITIONS au chargement et restaurer le template
  useEffect(() => {
    console.log("🎯 Initialisation avec DEFAULT_POSITIONS");
    console.log("QR Position:", DEFAULT_POSITIONS.QR);
    console.log("ID Position:", DEFAULT_POSITIONS.ID);

    // FORCER la réinitialisation des positions au chargement pour éviter les problèmes
    setQrPosition({
      x: DEFAULT_POSITIONS.QR.x,
      y: DEFAULT_POSITIONS.QR.y,
      size: DEFAULT_POSITIONS.QR.size,
    });
    setIdPosition({
      x: DEFAULT_POSITIONS.ID.x,
      y: DEFAULT_POSITIONS.ID.y,
      fontSize: DEFAULT_POSITIONS.ID.fontSize,
      color: DEFAULT_POSITIONS.ID.color,
    });
    console.log("🔄 Positions forcées aux valeurs par défaut");

    // Charger les templates sauvegardés si besoin
    loadSavedTemplates();

    // Restaurer l'image au chargement
    restoreSavedTemplate();
  }, []);

  // Upload du template
  // Fonction pour compresser l'image
  const compressImage = (file, maxWidth = 800, quality = 0.7) => {
    return new Promise((resolve) => {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");
      const img = new Image();

      img.onload = () => {
        // Calculer les nouvelles dimensions en gardant le ratio
        const ratio = Math.min(maxWidth / img.width, maxWidth / img.height);
        canvas.width = img.width * ratio;
        canvas.height = img.height * ratio;

        // Dessiner l'image redimensionnée
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        // Convertir en data URL avec compression
        const compressedDataUrl = canvas.toDataURL("image/jpeg", quality);
        resolve(compressedDataUrl);
      };

      img.src = URL.createObjectURL(file);
    });
  };

  const handleTemplateUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.match(/^image\/(png|jpeg|jpg)$/)) {
      alert("Veuillez sélectionner une image PNG ou JPEG");
      return;
    }

    try {
      setLoading(true);

      // Lire l'image originale en haute qualité pour le rendu
      const reader = new FileReader();
      reader.onload = async (e) => {
        const originalImage = e.target.result;
        setTemplate(originalImage);
        setTemplatePreview(originalImage);

        // Compresser l'image pour le stockage
        const compressedImage = await compressImage(file);

        // Sauvegarder automatiquement l'image compressée
        try {
          localStorage.setItem("currentTemplate", compressedImage);
          console.log("Template sauvegardé automatiquement (compressé)");
        } catch (storageError) {
          console.warn(
            "Impossible de sauvegarder le template:",
            storageError.message
          );
          // L'image restera disponible jusqu'à actualisation
        }
      };
      reader.readAsDataURL(file);
    } catch (error) {
      console.error("Erreur lors du traitement de l'image:", error);
      alert("Erreur lors du chargement de l'image");
    } finally {
      setLoading(false);
    }
  };

  // Générer un QR code
  const generateQRCode = async (staffId) => {
    console.log("🎯 generateQRCode appelé avec staffId:", staffId);
    console.log("📐 QR size:", qrPosition.size);
    try {
      const qrDataUrl = await QRCode.toDataURL(staffId, {
        width: qrPosition.size,
        margin: 1,
      });
      console.log("✅ QR code généré avec succès, longueur:", qrDataUrl.length);
      return qrDataUrl;
    } catch (error) {
      console.error("❌ Erreur génération QR:", error);
      return null;
    }
  };

  // Générer une carte pour un personnel
  const generateCard = async (staff) => {
    console.log("🎯 generateCard appelé pour:", staff.name);
    console.log(
      "📋 État template:",
      !!template,
      "templatePreview:",
      !!templatePreview
    );

    if (!template || !templatePreview) {
      console.log(
        "❌ Template manquant - template:",
        !!template,
        "templatePreview:",
        !!templatePreview
      );
      return null;
    }

    return new Promise((resolve) => {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");
      const img = new Image();

      img.onload = async () => {
        // Configurer le canvas aux dimensions de l'image
        canvas.width = img.width;
        canvas.height = img.height;

        console.log("✅ Template image chargée. Dimensions:", {
          width: img.width,
          height: img.height,
        });
        console.log("DEFAULT_POSITIONS.QR:", DEFAULT_POSITIONS.QR);
        console.log("State qrPosition:", qrPosition);
        console.log("State idPosition:", idPosition);

        // Dessiner le template
        ctx.drawImage(img, 0, 0);
        console.log("✅ Template dessiné sur canvas");

        // Générer et dessiner le QR code - Utiliser le VRAI QR code de la base de données
        const staffQRCode =
          staff.qr_code ||
          staff.staff_id ||
          staff.unique_id ||
          `${getStaffPrefix(selectedCategory)}${staff.id}`;
        const qrDataUrl = await generateQRCode(staffQRCode);

        console.log(`Staff: ${staff.name}, QR Code utilisé: ${staffQRCode}`);
        console.log(
          "📊 QR Data URL reçu:",
          !!qrDataUrl,
          qrDataUrl ? "longueur: " + qrDataUrl.length : "NULL"
        );
        if (qrDataUrl) {
          console.log("✅ QR code existe, création de l'image...");
          const qrImg = new Image();
          qrImg.onload = () => {
            console.log("✅ Image QR chargée, dessin sur canvas...");
            // UTILISER LES VRAIES POSITIONS DES SLIDERS (au lieu de forcer DEFAULT_POSITIONS)
            const qrX = qrPosition.x;
            const qrY = qrPosition.y;
            const qrSize = qrPosition.size;

            console.log(
              `USING SLIDER VALUES - QR at X=${qrX}, Y=${qrY}, Size=${qrSize}`
            );
            console.log(`Drawing QR at: X=${qrX}, Y=${qrY}, Size=${qrSize}`);
            ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

            // UTILISER LES VRAIES POSITIONS ID DES SLIDERS (au lieu de forcer DEFAULT_POSITIONS)
            const idX = idPosition.x;
            const idY = idPosition.y;
            const fontSize = idPosition.fontSize;

            ctx.font = `bold ${fontSize}px Arial`;
            ctx.fillStyle = idPosition.color || "#000000";
            ctx.textAlign = "left";

            console.log(
              `USING SLIDER VALUES - ID at X=${idX}, Y=${idY}, Font=${fontSize}px`
            );

            const idText = staffQRCode; // Utiliser le même QR code que pour la génération
            console.log(
              `Drawing ID "${idText}" at: X=${idX}, Y=${idY}, Font=${fontSize}px`
            );

            ctx.fillText(idText, idX, idY);

            // Canvas prêt - tous les éléments sont dessinés
            console.log("✅ Carte complète générée avec QR code et ID");

            resolve({
              staff: staff,
              cardDataUrl: canvas.toDataURL("image/png"),
              id: staffQRCode, // Utiliser le même QR code cohérent
            });
          };
          qrImg.onerror = (err) => {
            console.error("❌ Erreur chargement image QR:", err);
            resolve(null);
          };
          console.log("🔄 Assignation src à l'image QR...");
          qrImg.src = qrDataUrl;
        } else {
          console.log("❌ Pas de QR code généré, résolution sans QR");
          // Même si pas de QR, dessiner quand même l'ID
          const idX = idPosition.x;
          const idY = idPosition.y;
          const fontSize = idPosition.fontSize;

          ctx.font = `bold ${fontSize}px Arial`;
          ctx.fillStyle = idPosition.color || "#000000";
          ctx.textAlign = "left";

          const idText = staffQRCode;
          console.log(
            `Drawing ID "${idText}" at: X=${idX}, Y=${idY}, Font=${fontSize}px`
          );
          ctx.fillText(idText, idX, idY);

          resolve({
            staff: staff,
            cardDataUrl: canvas.toDataURL("image/png"),
            id: staffQRCode,
          });
        }
      };
      img.src = templatePreview;
    });
  };

  // Prévisualiser les cartes
  const handlePreview = async () => {
    console.log("=== PRÉVISUALISATION DES CARTES ===");
    console.log("Template chargé:", !!template);
    console.log("Template preview:", !!templatePreview);
    console.log("Personnel sélectionné:", selectedStaff.length);
    console.log("selectedStaff détail:", selectedStaff);

    if (!template || selectedStaff.length === 0) {
      alert("Veuillez sélectionner un template et du personnel");
      return;
    }

    if (!templatePreview) {
      alert("Erreur: Le template n'est pas chargé correctement");
      return;
    }

    setLoading(true);
    const cards = [];

    try {
      console.log(
        "Début génération des cartes pour",
        selectedStaff.length,
        "personnes"
      );

      for (let i = 0; i < selectedStaff.length; i++) {
        const staff = selectedStaff[i];
        console.log(
          `Génération carte ${i + 1}/${selectedStaff.length} pour:`,
          staff
        );

        try {
          // Ajouter un timeout pour éviter les blocages
          const card = await Promise.race([
            generateCard(staff),
            new Promise((_, reject) =>
              setTimeout(
                () => reject(new Error("Timeout génération carte")),
                10000
              )
            ),
          ]);

          if (card) {
            cards.push(card);
            console.log(
              `✅ Carte ${i + 1}/${
                selectedStaff.length
              } générée avec succès pour ${
                staff.name || staff.first_name + " " + staff.last_name
              }`
            );
          } else {
            console.log(
              `❌ Échec génération carte ${i + 1}/${
                selectedStaff.length
              } pour ${staff.name || staff.first_name + " " + staff.last_name}`
            );
          }
        } catch (error) {
          console.error(
            `Erreur génération carte pour ${staff.first_name} ${staff.last_name}:`,
            error
          );
        }
      }

      console.log("=== RÉSUMÉ GÉNÉRATION ===");
      console.log(`Personnel sélectionné: ${selectedStaff.length}`);
      console.log(`Cartes générées avec succès: ${cards.length}`);
      console.log(`Cartes échouées: ${selectedStaff.length - cards.length}`);
      console.log("Array cards avant setPreviewCards:", cards);
      console.log("=========================");

      setPreviewCards(cards);
      setShowPreviewModal(true);

      if (cards.length === 0) {
        alert(
          "Aucune carte n'a pu être générée. Vérifiez la console pour plus de détails."
        );
      } else if (cards.length < selectedStaff.length) {
        alert(
          `⚠️ Attention: Seulement ${cards.length} carte(s) générée(s) sur ${selectedStaff.length} sélectionnée(s). Vérifiez la console pour plus de détails.`
        );
      } else {
        // Toutes les cartes ont été générées avec succès
        console.log(
          "✅ Toutes les cartes générées avec succès. Prêt pour le PDF."
        );

        // Stocker les cartes dans une variable temporaire pour le PDF
        window.tempCards = cards;
      }
    } catch (error) {
      console.error("Erreur lors de la génération des cartes:", error);
      alert(
        "Erreur lors de la génération des cartes. Vérifiez la console pour plus de détails."
      );
    }

    setLoading(false);
  };

  // Générer un PDF A4 avec 10 cartes par page (grille 2x5)
  const generatePDFA4 = async (cardsToGenerate = null) => {
    // Utiliser les cartes passées en paramètre ou celles de l'état
    const cardsToUse = cardsToGenerate || previewCards;

    console.log("=== GÉNÉRATION PDF A4 ===");
    console.log("Nombre de cartes sélectionnées:", selectedStaff.length);
    console.log("Nombre de cartes à utiliser:", cardsToUse.length);
    console.log(
      "Source des cartes:",
      cardsToGenerate ? "Paramètre direct" : "previewCards state"
    );
    console.log("cardsToUse:", cardsToUse);

    if (cardsToUse.length === 0) {
      alert(
        "Aucune carte à générer. Veuillez d'abord prévisualiser les cartes."
      );
      return;
    }

    try {
      setLoading(true);

      // Importer jsPDF dynamiquement
      const { jsPDF } = await import("jspdf");

      const pdf = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4",
      });

      const pageWidth = 210; // A4 width in mm
      const pageHeight = 297; // A4 height in mm

      // Marges pour l'impression
      const margin = 10;

      // Espacement entre cartes
      const spacing = 5;

      // Calculer les dimensions basées sur le ratio 1920x1080 (16:9)
      const cardRatio = 1920 / 1080; // Ratio largeur/hauteur = 1.778

      // Calculer les dimensions pour 10 cartes par page (2x5 - 2 colonnes, 5 lignes)
      // AUGMENTER LA TAILLE DES CARTES en réduisant les marges
      const reducedMargin = 5; // Réduire les marges
      const reducedSpacing = 3; // Réduire l'espacement

      const availableHeight =
        pageHeight - 2 * reducedMargin - 4 * reducedSpacing; // 4 espacements pour 5 lignes
      const availableWidth = pageWidth - 2 * reducedMargin - reducedSpacing; // 1 espacement pour 2 colonnes

      const cardHeight = availableHeight / 5; // 5 lignes par page
      const cardWidth = availableWidth / 2; // 2 colonnes par page

      // Vérifier si le ratio 1920x1080 peut être respecté
      const targetCardWidth = cardHeight * cardRatio;

      let finalCardWidth, finalCardHeight;
      if (targetCardWidth <= cardWidth) {
        // Le ratio peut être respecté avec la hauteur disponible
        finalCardWidth = targetCardWidth;
        finalCardHeight = cardHeight;
      } else {
        // Ajuster par la largeur disponible
        finalCardWidth = cardWidth;
        finalCardHeight = cardWidth / cardRatio;
      }

      console.log(
        `Dimensions calculées (ratio 1920x1080): ${finalCardWidth.toFixed(
          1
        )}mm x ${finalCardHeight.toFixed(1)}mm par carte`
      );
      console.log(
        `Ratio respecté: ${(finalCardWidth / finalCardHeight).toFixed(
          3
        )} (cible: 1.778)`
      );

      // Positions pour 10 cartes par page (grille 2x5) - optimisé pour 1920x1080
      const centerOffsetX =
        (pageWidth - (2 * finalCardWidth + reducedSpacing)) / 2;
      const centerOffsetY =
        (pageHeight - (5 * finalCardHeight + 4 * reducedSpacing)) / 2;

      const positions = [
        // Ligne 1 (2 cartes)
        { x: centerOffsetX, y: centerOffsetY }, // Carte 1
        {
          x: centerOffsetX + finalCardWidth + reducedSpacing,
          y: centerOffsetY,
        }, // Carte 2
        // Ligne 2 (2 cartes)
        {
          x: centerOffsetX,
          y: centerOffsetY + finalCardHeight + reducedSpacing,
        }, // Carte 3
        {
          x: centerOffsetX + finalCardWidth + reducedSpacing,
          y: centerOffsetY + finalCardHeight + reducedSpacing,
        }, // Carte 4
        // Ligne 3 (2 cartes)
        {
          x: centerOffsetX,
          y: centerOffsetY + 2 * (finalCardHeight + reducedSpacing),
        }, // Carte 5
        {
          x: centerOffsetX + finalCardWidth + reducedSpacing,
          y: centerOffsetY + 2 * (finalCardHeight + reducedSpacing),
        }, // Carte 6
        // Ligne 4 (2 cartes)
        {
          x: centerOffsetX,
          y: centerOffsetY + 3 * (finalCardHeight + reducedSpacing),
        }, // Carte 7
        {
          x: centerOffsetX + finalCardWidth + reducedSpacing,
          y: centerOffsetY + 3 * (finalCardHeight + reducedSpacing),
        }, // Carte 8
        // Ligne 5 (2 cartes)
        {
          x: centerOffsetX,
          y: centerOffsetY + 4 * (finalCardHeight + reducedSpacing),
        }, // Carte 9
        {
          x: centerOffsetX + finalCardWidth + reducedSpacing,
          y: centerOffsetY + 4 * (finalCardHeight + reducedSpacing),
        }, // Carte 10
      ];

      // Les dimensions sont déjà dans finalCardWidth et finalCardHeight

      for (let i = 0; i < cardsToUse.length; i++) {
        const card = cardsToUse[i];

        // Nouvelle page après chaque 10 cartes (sauf pour la première page)
        if (i > 0 && i % 10 === 0) {
          pdf.addPage();
          console.log(`Nouvelle page créée pour la carte ${i + 1}`);
        }

        // Position sur la page : 0,1,2,3,4,5,6,7,8,9 pour grille 2x5
        const positionIndex = i % 10;
        const position = positions[positionIndex];

        console.log(
          `Carte ${i + 1}: Position ${positionIndex} (${position.x}, ${
            position.y
          })`
        );

        try {
          // Convertir le data URL en image et l'ajouter au PDF
          pdf.addImage(
            card.cardDataUrl,
            "PNG",
            position.x,
            position.y,
            finalCardWidth,
            finalCardHeight
          );

          console.log(`Carte ${i + 1} ajoutée avec succès`);
        } catch (error) {
          console.error(`Erreur ajout carte ${i + 1}:`, error);
        }
      }

      // Ajouter des lignes de découpe pour 10 cartes (grille 2x5)
      console.log("Ajout des lignes de découpe pour 10 cartes...");

      pdf.setDrawColor(200, 200, 200); // Gris clair
      pdf.setLineWidth(0.1);

      // Ligne verticale centrale (1 ligne pour séparer 2 colonnes)
      const centerX = centerOffsetX + finalCardWidth + reducedSpacing / 2;
      pdf.line(centerX, 0, centerX, pageHeight);

      // Lignes horizontales (4 lignes pour séparer 5 lignes)
      for (let i = 1; i < 5; i++) {
        const lineY =
          centerOffsetY +
          i * (finalCardHeight + reducedSpacing) -
          reducedSpacing / 2;
        pdf.line(0, lineY, pageWidth, lineY);
      }

      // Bordure délimitant toute la zone des 10 cartes
      pdf.setDrawColor(150, 150, 150); // Gris plus foncé
      pdf.rect(
        centerOffsetX,
        centerOffsetY,
        2 * finalCardWidth + reducedSpacing,
        5 * finalCardHeight + 4 * reducedSpacing
      );

      // Télécharger le PDF
      const filename = `cartes_personnel_${cardsToUse.length}_cartes_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      pdf.save(filename);

      alert(
        `PDF généré avec succès !\n${
          cardsToUse.length
        } carte(s) sur ${Math.ceil(
          cardsToUse.length / 10
        )} page(s) A4\nDimensions: ${finalCardWidth.toFixed(
          1
        )}mm x ${finalCardHeight.toFixed(
          1
        )}mm par carte\nFormat: 1920x1080px (ratio 16:9) optimisé\nDisposition: 10 cartes par page (2x5) centrées\nLignes de découpe incluses pour faciliter la coupe`
      );
    } catch (error) {
      console.error("Erreur génération PDF:", error);
      alert(
        "Erreur lors de la génération du PDF. Assurez-vous que jsPDF est installé."
      );
    } finally {
      setLoading(false);
    }
  };

  // Télécharger les cartes individuellement
  const downloadCards = () => {
    previewCards.forEach((card, index) => {
      const link = document.createElement("a");
      link.download = `carte_${card.id}_${index + 1}.png`;
      link.href = card.cardDataUrl;
      link.click();
    });
  };

  // Configuration visuelle du template
  const TemplateConfigurator = () => {
    const [imageDimensions, setImageDimensions] = useState({
      width: 600,
      height: 400,
    });
    const [isDragging, setIsDragging] = useState(null); // 'qr' ou 'id'
    const imgRef = useRef(null);

    useEffect(() => {
      if (imgRef.current && imgRef.current.naturalWidth) {
        const img = imgRef.current;
        const containerWidth = 600;
        const scale = containerWidth / img.naturalWidth;
        setImageDimensions({
          width: img.naturalWidth,
          height: img.naturalHeight,
          displayWidth: containerWidth,
          displayHeight: img.naturalHeight * scale,
          scale: scale,
        });
      }
    }, [templatePreview, qrPosition, idPosition]); // Ajouter les positions pour mise à jour temps réel

    // Gérer le drag & drop manuel
    const handleMouseDown = (e, elementType) => {
      e.preventDefault();
      setIsDragging(elementType);
    };

    const handleMouseMove = (e) => {
      if (!isDragging || !imgRef.current) return;

      const rect = imgRef.current.getBoundingClientRect();
      const scale = imageDimensions.scale || 1;

      // Calculer les nouvelles coordonnées en pixels réels
      const newX = Math.max(
        0,
        Math.min(imageDimensions.width, (e.clientX - rect.left) / scale)
      );
      const newY = Math.max(
        0,
        Math.min(imageDimensions.height, (e.clientY - rect.top) / scale)
      );

      if (isDragging === "qr") {
        setQrPosition({
          ...qrPosition,
          x: Math.round(newX - qrPosition.size / 2), // Centrer sur la souris
          y: Math.round(newY - qrPosition.size / 2),
        });
      } else if (isDragging === "id") {
        setIdPosition({
          ...idPosition,
          x: Math.round(newX),
          y: Math.round(newY),
        });
      }
    };

    const handleMouseUp = () => {
      setIsDragging(null);
    };

    // Ajouter les event listeners
    useEffect(() => {
      if (isDragging) {
        document.addEventListener("mousemove", handleMouseMove);
        document.addEventListener("mouseup", handleMouseUp);
        return () => {
          document.removeEventListener("mousemove", handleMouseMove);
          document.removeEventListener("mouseup", handleMouseUp);
        };
      }
    }, [isDragging, imageDimensions, qrPosition, idPosition]);

    return (
      <div className="position-relative" style={{ maxWidth: "600px" }}>
        {templatePreview && (
          <div className="position-relative d-inline-block">
            <img
              ref={imgRef}
              src={templatePreview}
              alt="Template"
              style={{ maxWidth: "100%", height: "auto" }}
              onLoad={(e) => {
                const img = e.target;
                const containerWidth = 600;
                const scale = containerWidth / img.naturalWidth;
                setImageDimensions({
                  width: img.naturalWidth,
                  height: img.naturalHeight,
                  displayWidth: containerWidth,
                  displayHeight: img.naturalHeight * scale,
                  scale: scale,
                });
              }}
            />

            {/* Indicateur position QR - DRAGGABLE MANUELLEMENT */}
            <div
              className="position-absolute border border-danger bg-danger bg-opacity-25 d-flex align-items-center justify-content-center"
              style={{
                left: `${qrPosition.x * (imageDimensions.scale || 1)}px`,
                top: `${qrPosition.y * (imageDimensions.scale || 1)}px`,
                width: `${qrPosition.size * (imageDimensions.scale || 1)}px`,
                height: `${qrPosition.size * (imageDimensions.scale || 1)}px`,
                cursor: isDragging === "qr" ? "grabbing" : "grab",
                zIndex: 10,
                userSelect: "none",
                border:
                  isDragging === "qr"
                    ? "3px solid #ff0000"
                    : "2px solid #dc3545",
                boxShadow:
                  isDragging === "qr" ? "0 0 10px rgba(255,0,0,0.5)" : "none",
              }}
              title={`QR Code: ${qrPosition.x}x${qrPosition.y}, Taille: ${qrPosition.size}px - Cliquez et glissez pour déplacer`}
              onMouseDown={(e) => handleMouseDown(e, "qr")}
            >
              <small className="text-danger fw-bold">QR</small>
            </div>

            {/* Indicateur position ID - DRAGGABLE MANUELLEMENT */}
            <div
              className="position-absolute"
              style={{
                left: `${idPosition.x * (imageDimensions.scale || 1)}px`,
                top: `${idPosition.y * (imageDimensions.scale || 1)}px`,
                fontSize: `${
                  idPosition.fontSize * (imageDimensions.scale || 1)
                }px`,
                color: idPosition.color || "red",
                fontWeight: "bold",
                cursor: isDragging === "id" ? "grabbing" : "grab",
                backgroundColor:
                  isDragging === "id"
                    ? "rgba(255,255,0,0.9)"
                    : "rgba(255,255,255,0.9)",
                padding: "4px 8px",
                borderRadius: "4px",
                border:
                  isDragging === "id" ? "3px solid #ff0000" : "2px solid red",
                zIndex: 10,
                userSelect: "none",
                boxShadow:
                  isDragging === "id" ? "0 0 10px rgba(255,0,0,0.5)" : "none",
                transition: isDragging === "id" ? "none" : "all 0.2s ease",
              }}
              title={`ID: ${idPosition.x}x${idPosition.y}, Police: ${idPosition.fontSize}px - Cliquez et glissez pour déplacer`}
              onMouseDown={(e) => handleMouseDown(e, "id")}
            >
              STAF_001
            </div>
          </div>
        )}
      </div>
    );
  };

  return (
    <Container fluid>
      <Row className="mb-4">
        <Col>
          <h2>
            <QrCode className="me-2" />
            Générateur de Cartes Personnel
          </h2>
          <p className="text-muted">
            Créez et imprimez des cartes personnalisées pour tout le personnel
            de l'établissement
          </p>
        </Col>
      </Row>

      <Tabs defaultActiveKey="template" className="mb-4">
        {/* Tab 1: Configuration Template */}
        <Tab
          eventKey="template"
          title={
            <>
              <ImageIcon className="me-1" />
              Template
            </>
          }
        >
          <Card>
            <Card.Header>
              <h5>
                <Upload className="me-2" />
                Configuration du Template
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label>Template de carte (PNG/JPEG)</Form.Label>
                    <Form.Control
                      type="file"
                      accept="image/png,image/jpeg,image/jpg"
                      onChange={handleTemplateUpload}
                      ref={fileInputRef}
                    />
                    <Form.Text className="text-muted">
                      Utilisez un template propre sans nom ni rôle
                      <br />
                      💾 L'image sera automatiquement sauvegardée (version
                      compressée pour éviter les erreurs de stockage)
                    </Form.Text>
                  </Form.Group>

                  {template && (
                    <div className="mb-3">
                      <Badge bg="success" className="me-2">
                        Template chargé
                      </Badge>
                      <Button
                        variant="outline-primary"
                        size="sm"
                        className="me-2"
                        onClick={() => setShowConfigModal(true)}
                      >
                        <Gear className="me-1" />
                        Configurer positions
                      </Button>
                      <Button
                        variant="outline-danger"
                        size="sm"
                        onClick={() => {
                          setTemplate(null);
                          setTemplatePreview(null);
                          localStorage.removeItem("currentTemplate");
                          if (fileInputRef.current) {
                            fileInputRef.current.value = "";
                          }
                        }}
                      >
                        🗑️ Supprimer template
                      </Button>
                    </div>
                  )}
                </Col>
                <Col md={6}>
                  {templatePreview && (
                    <div>
                      <h6>Aperçu du template :</h6>
                      <img
                        src={templatePreview}
                        alt="Template preview"
                        style={{
                          maxWidth: "100%",
                          height: "auto",
                          maxHeight: "300px",
                        }}
                        className="border rounded"
                      />
                    </div>
                  )}
                </Col>
              </Row>

              {/* Section simplifiée - plus de templates sauvegardés */}
              <hr />
              <Row>
                <Col>
                  <Alert variant="info">
                    <strong>💡 Positions par défaut :</strong>
                    <br />• QR Code : X={DEFAULT_POSITIONS.QR.x}, Y=
                    {DEFAULT_POSITIONS.QR.y}, Taille={DEFAULT_POSITIONS.QR.size}
                    px
                    <br />• ID Personnel : X={DEFAULT_POSITIONS.ID.x}, Y=
                    {DEFAULT_POSITIONS.ID.y}, Police=
                    {DEFAULT_POSITIONS.ID.fontSize}px
                    <br />
                    <small className="text-muted">
                      Ces positions sont utilisées automatiquement et peuvent
                      être ajustées manuellement.
                    </small>
                    <div className="mt-2">
                      <Button
                        variant="outline-primary"
                        size="sm"
                        onClick={() => {
                          setQrPosition({
                            x: DEFAULT_POSITIONS.QR.x,
                            y: DEFAULT_POSITIONS.QR.y,
                            size: DEFAULT_POSITIONS.QR.size,
                          });
                          setIdPosition({
                            x: DEFAULT_POSITIONS.ID.x,
                            y: DEFAULT_POSITIONS.ID.y,
                            fontSize: DEFAULT_POSITIONS.ID.fontSize,
                            color: DEFAULT_POSITIONS.ID.color,
                          });
                          alert(
                            "Positions réinitialisées aux valeurs par défaut !"
                          );
                        }}
                      >
                        🔄 Réinitialiser les positions
                      </Button>
                    </div>
                  </Alert>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Tab>

        {/* Tab 2: Sélection Personnel */}
        <Tab
          eventKey="staff"
          title={
            <>
              <People className="me-1" />
              Personnel
            </>
          }
        >
          <Card>
            <Card.Header>
              <h5>
                <People className="me-2" />
                Sélection du Personnel
              </h5>
            </Card.Header>
            <Card.Body>
              {selectedCategory === "all_staff" && (
                <Alert variant="info" className="mb-3">
                  <strong>🎯 TOUT LE PERSONNEL :</strong> Cette option charge{" "}
                  <strong>TOUS</strong> les utilisateurs du système :<br />
                  👤 <strong>Utilisateurs système</strong> : Personnel avec
                  comptes (82 personnes)
                  <br />
                  👨‍🏫 <strong>Enseignants sans comptes</strong> : Personnel sans
                  compte système (71 enseignants)
                  <br />
                  📊 <strong>TOTAL ATTENDU</strong> : 153 personnes (selon
                  vérification BD)
                </Alert>
              )}

              <Row>
                <Col md={4}>
                  <Form.Group className="mb-3">
                    <Form.Label>Catégorie de personnel</Form.Label>
                    <Form.Select
                      value={selectedCategory}
                      onChange={(e) => {
                        setSelectedCategory(e.target.value);
                        loadStaffByCategory(e.target.value);
                      }}
                    >
                      <option value="">Sélectionnez une catégorie</option>
                      {categories.map((cat) => (
                        <option key={cat.value} value={cat.value}>
                          {cat.label}
                        </option>
                      ))}
                    </Form.Select>
                    {selectedCategory && (
                      <Form.Text className="text-muted">
                        {
                          categories.find(
                            (cat) => cat.value === selectedCategory
                          )?.description
                        }
                      </Form.Text>
                    )}
                  </Form.Group>
                </Col>
                <Col md={8}>
                  {loading && (
                    <Spinner animation="border" size="sm" className="me-2" />
                  )}
                  {staffList.length > 0 && (
                    <div>
                      <h6>Personnel disponible ({staffList.length}) :</h6>
                      <div style={{ maxHeight: "300px", overflowY: "auto" }}>
                        {staffList.map((staff) => {
                          const staffId =
                            staff.staff_id ||
                            staff.qr_code ||
                            staff.unique_id ||
                            `${getStaffPrefix(selectedCategory)}${staff.id}`;
                          const sourceIcon =
                            staff.source === "users" ? "👤" : "👨‍🏫";
                          const roleText =
                            staff.source === "users"
                              ? ` (${staff.role})`
                              : staff.type_personnel
                              ? ` (${
                                  staff.type_personnel === "P"
                                    ? "Permanent"
                                    : staff.type_personnel === "S"
                                    ? "Semi-perm"
                                    : "Vacataire"
                                })`
                              : "";

                          return (
                            <Form.Check
                              key={`${staff.source}-${staff.id}`}
                              type="checkbox"
                              label={
                                <span>
                                  {sourceIcon} <strong>{staff.name}</strong> -{" "}
                                  {staffId}
                                  {roleText}
                                  {staff.source === "users" && (
                                    <span className="text-muted">
                                      {" "}
                                      (Compte système)
                                    </span>
                                  )}
                                  {staff.source === "teachers" && (
                                    <span className="text-muted">
                                      {" "}
                                      (Sans compte)
                                    </span>
                                  )}
                                </span>
                              }
                              checked={selectedStaff.some(
                                (s) =>
                                  s.id === staff.id && s.source === staff.source
                              )}
                              onChange={(e) => {
                                if (e.target.checked) {
                                  setSelectedStaff([...selectedStaff, staff]);
                                } else {
                                  setSelectedStaff(
                                    selectedStaff.filter(
                                      (s) =>
                                        !(
                                          s.id === staff.id &&
                                          s.source === staff.source
                                        )
                                    )
                                  );
                                }
                              }}
                              className="mb-2"
                            />
                          );
                        })}
                      </div>

                      <div className="mt-3">
                        <Badge bg="primary" className="me-2">
                          {selectedStaff.length} sélectionné(s)
                        </Badge>
                        <Button
                          variant="outline-secondary"
                          size="sm"
                          onClick={() => setSelectedStaff(staffList)}
                          className="me-2"
                        >
                          Tout sélectionner
                        </Button>
                        <Button
                          variant="outline-secondary"
                          size="sm"
                          onClick={() => setSelectedStaff([])}
                        >
                          Tout désélectionner
                        </Button>
                      </div>
                    </div>
                  )}
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Tab>

        {/* Tab 3: Génération */}
        <Tab
          eventKey="generate"
          title={
            <>
              <Download className="me-1" />
              Génération
            </>
          }
        >
          <Card>
            <Card.Header>
              <h5>
                <Download className="me-2" />
                Génération et Téléchargement
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col>
                  <Alert variant="info">
                    <strong>Récapitulatif :</strong>
                    <br />• Template : {template ? "✓ Chargé" : "✗ Non chargé"}
                    <br />• Personnel sélectionné : {selectedStaff.length}{" "}
                    personne(s)
                    <br />• Catégorie :{" "}
                    {categories.find((c) => c.value === selectedCategory)
                      ?.label || "Aucune"}
                  </Alert>

                  <div className="d-flex gap-3">
                    <Button
                      variant="primary"
                      onClick={handlePreview}
                      disabled={
                        !template || selectedStaff.length === 0 || loading
                      }
                    >
                      {loading ? (
                        <Spinner
                          animation="border"
                          size="sm"
                          className="me-2"
                        />
                      ) : (
                        <Eye className="me-2" />
                      )}
                      Prévisualiser les cartes
                    </Button>

                    {previewCards.length > 0 && (
                      <>
                        <Button
                          variant="success"
                          onClick={downloadCards}
                          className="me-2"
                        >
                          <Download className="me-2" />
                          Télécharger individuelles ({previewCards.length})
                        </Button>
                        <Button
                          variant="primary"
                          onClick={() => generatePDFA4(window.tempCards)}
                        >
                          <Download className="me-2" />
                          Générer PDF A4 (10 par page - 2x5)
                        </Button>
                      </>
                    )}
                  </div>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Tab>
      </Tabs>

      {/* Modal Configuration Positions */}
      <Modal
        show={showConfigModal}
        onHide={() => setShowConfigModal(false)}
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            <Gear className="me-2" />
            Configuration des Positions
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col md={8}>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h6 className="mb-0">Aperçu du template avec positions :</h6>
                <div className="d-flex gap-2">
                  <Badge bg="danger" className="d-flex align-items-center">
                    <div
                      className="me-1"
                      style={{
                        width: "12px",
                        height: "12px",
                        backgroundColor: "#dc3545",
                        border: "1px solid white",
                      }}
                    ></div>
                    QR Code
                  </Badge>
                  <Badge bg="warning" className="d-flex align-items-center">
                    <div
                      className="me-1"
                      style={{
                        width: "12px",
                        height: "12px",
                        backgroundColor: "#ffc107",
                        border: "1px solid white",
                      }}
                    ></div>
                    ID Personnel
                  </Badge>
                </div>
              </div>
              <div
                className="border rounded p-3"
                style={{ backgroundColor: "#f8f9fa" }}
              >
                <TemplateConfigurator />
                <small className="text-muted d-block mt-2">
                  💡 <strong>Astuce :</strong> Cliquez et glissez les éléments
                  rouge (QR) et jaune (ID) directement sur l'image pour les
                  positionner manuellement !
                </small>
              </div>
            </Col>
            <Col md={4}>
              <h6>Position du QR Code :</h6>
              <div className="d-flex gap-2 mb-3">
                <Button
                  variant="outline-info"
                  size="sm"
                  onClick={() => {
                    setQrPosition({
                      x: DEFAULT_POSITIONS.QR.x,
                      y: DEFAULT_POSITIONS.QR.y,
                      size: DEFAULT_POSITIONS.QR.size,
                    });
                  }}
                >
                  Position par défaut
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQrPosition({ x: 50, y: 50, size: 80 })}
                >
                  Haut-gauche
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQrPosition({ x: 1150, y: 50, size: 80 })}
                >
                  Haut-droite
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQrPosition({ x: 50, y: 800, size: 80 })}
                >
                  Bas-gauche
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQrPosition({ x: 1150, y: 800, size: 80 })}
                >
                  Bas-droite
                </Button>
              </div>

              <div className="mb-3">
                <Button
                  variant="outline-warning"
                  size="sm"
                  onClick={() => {
                    localStorage.removeItem("cardTemplates");
                    setCurrentTemplateId(null);
                    setTemplateName("");
                    setSavedTemplates([]);
                    setQrPosition({
                      x: DEFAULT_POSITIONS.QR.x,
                      y: DEFAULT_POSITIONS.QR.y,
                      size: DEFAULT_POSITIONS.QR.size,
                    });
                    setIdPosition({
                      x: DEFAULT_POSITIONS.ID.x,
                      y: DEFAULT_POSITIONS.ID.y,
                      fontSize: DEFAULT_POSITIONS.ID.fontSize,
                      color: DEFAULT_POSITIONS.ID.color,
                    });
                    alert("Cache nettoyé et positions réinitialisées !");
                  }}
                  className="me-2"
                >
                  🗑️ Réinitialiser tout
                </Button>
                <Badge bg="info" className="p-2">
                  💡 Astuce: Utilisez les boutons ou les sliders ci-dessous pour
                  ajuster précisément
                </Badge>
              </div>
              <Form.Group className="mb-2">
                <Form.Label>Position X: {qrPosition.x}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="1200"
                    value={qrPosition.x}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        x: parseInt(e.target.value) || 0,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="0"
                    max="1200"
                    value={qrPosition.x}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        x: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
                <Form.Text className="text-muted">
                  Position horizontale depuis la gauche
                </Form.Text>
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Position Y: {qrPosition.y}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="900"
                    value={qrPosition.y}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        y: parseInt(e.target.value) || 0,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="0"
                    max="900"
                    value={qrPosition.y}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        y: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
                <Form.Text className="text-muted">
                  Position verticale depuis le haut
                </Form.Text>
              </Form.Group>
              <Form.Group className="mb-3">
                <Form.Label>
                  Taille du QR (carré): {qrPosition.size}px × {qrPosition.size}
                  px
                </Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="40"
                    max="150"
                    value={qrPosition.size}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        size: parseInt(e.target.value) || 80,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="40"
                    max="150"
                    value={qrPosition.size}
                    onChange={(e) =>
                      setQrPosition({
                        ...qrPosition,
                        size: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
                <Form.Text className="text-muted">
                  Le QR code reste toujours carré pour être scannable
                </Form.Text>
              </Form.Group>

              <hr />

              <h6>Position de l'ID :</h6>
              <div className="d-flex gap-2 mb-3">
                <Button
                  variant="outline-info"
                  size="sm"
                  onClick={() => {
                    setIdPosition({
                      x: DEFAULT_POSITIONS.ID.x,
                      y: DEFAULT_POSITIONS.ID.y,
                      fontSize: DEFAULT_POSITIONS.ID.fontSize,
                      color: DEFAULT_POSITIONS.ID.color,
                    });
                  }}
                >
                  Position par défaut
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() =>
                    setIdPosition({ ...idPosition, x: 100, y: 300 })
                  }
                >
                  Centre-gauche
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() =>
                    setIdPosition({ ...idPosition, x: 600, y: 300 })
                  }
                >
                  Centre
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() =>
                    setIdPosition({ ...idPosition, x: 1000, y: 300 })
                  }
                >
                  Centre-droite
                </Button>
              </div>
              <Form.Group className="mb-2">
                <Form.Label>Position X: {idPosition.x}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="1200"
                    value={idPosition.x}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        x: parseInt(e.target.value) || 0,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="0"
                    max="1200"
                    value={idPosition.x}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        x: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
                <Form.Text className="text-muted">
                  Position horizontale de l'ID
                </Form.Text>
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Position Y: {idPosition.y}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="900"
                    value={idPosition.y}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        y: parseInt(e.target.value) || 0,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="0"
                    max="900"
                    value={idPosition.y}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        y: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
                <Form.Text className="text-muted">
                  Position verticale de l'ID
                </Form.Text>
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Taille police: {idPosition.fontSize}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="12"
                    max="48"
                    value={idPosition.fontSize}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        fontSize: parseInt(e.target.value) || 24,
                      })
                    }
                    style={{ width: "100px" }}
                  />
                  <Form.Range
                    min="12"
                    max="48"
                    value={idPosition.fontSize}
                    onChange={(e) =>
                      setIdPosition({
                        ...idPosition,
                        fontSize: parseInt(e.target.value),
                      })
                    }
                    className="flex-grow-1"
                  />
                </div>
              </Form.Group>
              <Form.Group className="mb-3">
                <Form.Label>Couleur :</Form.Label>
                <Form.Control
                  type="color"
                  value={idPosition.color}
                  onChange={(e) =>
                    setIdPosition({ ...idPosition, color: e.target.value })
                  }
                />
              </Form.Group>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowConfigModal(false)}>
            Fermer
          </Button>
          <Button variant="primary" onClick={() => setShowConfigModal(false)}>
            <Save className="me-1" />
            Sauvegarder
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal Prévisualisation */}
      <Modal
        show={showPreviewModal}
        onHide={() => setShowPreviewModal(false)}
        size="xl"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            <Eye className="me-2" />
            Prévisualisation des Cartes
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            {previewCards.map((card, index) => (
              <Col md={4} key={index} className="mb-3">
                <Card className="h-100">
                  <Card.Body className="text-center">
                    <img
                      src={card.cardDataUrl}
                      alt={`Carte ${card.staff.name}`}
                      style={{ maxWidth: "100%", height: "auto" }}
                      className="border rounded"
                    />
                    <h6 className="mt-2">{card.staff.name}</h6>
                    <Badge bg="primary">{card.id}</Badge>
                  </Card.Body>
                </Card>
              </Col>
            ))}
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button
            variant="secondary"
            onClick={() => setShowPreviewModal(false)}
          >
            Fermer
          </Button>
          <Button variant="success" onClick={downloadCards}>
            <Download className="me-1" />
            Télécharger toutes
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal de sauvegarde supprimé - utilise les positions par défaut du code */}

      <canvas ref={canvasRef} style={{ display: "none" }} />
    </Container>
  );
};

export default CardGenerator;
