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
  Trash,
  Upload,
} from "react-bootstrap-icons";
import { useAuth } from "../../hooks/useAuth";
import { secureApiEndpoints } from "../../utils/apiMigration";

// ========================================
// CONFIGURATION DES POSITIONS PAR DÉFAUT
// ========================================
// Modifiez ces valeurs pour changer les positions par défaut
const DEFAULT_POSITIONS = {
  // Position du QR Code - Centré en bas où il y a "Phone"
  QR: {
    x: 220, // Position horizontale (pixels depuis la gauche) - centré
    y: 790, // Position verticale (pixels depuis le haut) - en bas
    size: 120, // Taille du QR code (carré)
  },

  // Position de l'ID (STAF_XX) - Centré en bas de "PERSONNEL"
  ID: {
    x: 150, // Position horizontale (pixels depuis la gauche) - un peu à droite
    y: 620, // Position verticale (pixels depuis le haut) - plus bas sous "PERSONNEL"
    fontSize: 70, // Taille de la police
    color: "#000", // Couleur du texte
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
  const [qrPosition, setQrPosition] = useState({
    x: DEFAULT_POSITIONS.QR.x,
    y: DEFAULT_POSITIONS.QR.y,
    size: DEFAULT_POSITIONS.QR.size,
  });
  const [idPosition, setIdPosition] = useState({
    x: DEFAULT_POSITIONS.ID.x,
    y: DEFAULT_POSITIONS.ID.y,
    fontSize: DEFAULT_POSITIONS.ID.fontSize,
    color: DEFAULT_POSITIONS.ID.color,
  });
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
    { value: "admin", label: "Personnel Administratif", prefix: "STAF_" },
    {
      value: "teacher_permanent",
      label: "Enseignants Permanents",
      prefix: "TCH_",
    },
    {
      value: "teacher_semi",
      label: "Enseignants Semi-permanents",
      prefix: "TCH_",
    },
    {
      value: "teacher_vacataire",
      label: "Enseignants Vacataires",
      prefix: "TCH_",
    },
  ];

  // Fonction pour obtenir le préfixe selon la catégorie
  const getStaffPrefix = (categoryValue) => {
    const category = categories.find(cat => cat.value === categoryValue);
    return category ? category.prefix : "STAF_";
  };

  // Charger la liste du personnel selon la catégorie
  const loadStaffByCategory = async (category) => {
    if (!category) return;

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

  // Charger les templates sauvegardés (temporairement depuis localStorage)
  const loadSavedTemplates = async () => {
    try {
      // Temporaire : utiliser localStorage en attendant l'API
      const savedTemplatesData = localStorage.getItem("cardTemplates");
      const templates = savedTemplatesData
        ? JSON.parse(savedTemplatesData)
        : [];
      setSavedTemplates(templates);

      // DÉSACTIVÉ : Ne plus charger automatiquement le template par défaut
      // pour permettre l'utilisation des positions du code source
      // const defaultTemplate = templates.find((t) => t.is_default);
      // if (defaultTemplate) {
      //   await loadTemplate(defaultTemplate);
      // }
    } catch (error) {
      console.error("Erreur lors du chargement des templates:", error);
      // Fallback vers un tableau vide
      setSavedTemplates([]);
    }
  };

  // Charger un template spécifique
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

      // Charger l'image du template si elle existe
      if (templateData.template_preview) {
        setTemplatePreview(templateData.template_preview);
        // Créer un fichier fictif pour maintenir la compatibilité
        const response = await fetch(templateData.template_preview);
        const blob = await response.blob();
        const file = new File([blob], "template.png", { type: blob.type });
        setTemplate(file);
      }
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

      // Créer l'objet template
      const templateData = {
        id: currentTemplateId || Date.now().toString(),
        name: templateName,
        template_preview: templatePreview, // Stocker l'image en base64
        qr_x: qrPosition.x,
        qr_y: qrPosition.y,
        qr_size: qrPosition.size,
        id_x: idPosition.x,
        id_y: idPosition.y,
        id_font_size: idPosition.fontSize,
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

  // useEffect pour charger les templates au démarrage
  useEffect(() => {
    // Charger les templates mais ne pas appliquer automatiquement le template par défaut
    const loadTemplatesWithoutDefault = async () => {
      try {
        const savedTemplatesData = localStorage.getItem("cardTemplates");
        const templates = savedTemplatesData
          ? JSON.parse(savedTemplatesData)
          : [];
        setSavedTemplates(templates);

        // Commenté temporairement pour éviter l'écrasement des positions
        // const defaultTemplate = templates.find(t => t.is_default);
        // if (defaultTemplate) {
        //     await loadTemplate(defaultTemplate);
        // }
      } catch (error) {
        console.error("Erreur lors du chargement des templates:", error);
        setSavedTemplates([]);
      }
    };

    loadTemplatesWithoutDefault();
  }, []);

  // Upload du template
  const handleTemplateUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.match(/^image\/(png|jpeg|jpg)$/)) {
      alert("Veuillez sélectionner une image PNG ou JPEG");
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      setTemplate(e.target.result);
      setTemplatePreview(e.target.result);
    };
    reader.readAsDataURL(file);
  };

  // Générer un QR code
  const generateQRCode = async (staffId) => {
    try {
      return await QRCode.toDataURL(staffId, {
        width: qrPosition.size,
        margin: 1,
      });
    } catch (error) {
      console.error("Erreur génération QR:", error);
      return null;
    }
  };

  // Générer une carte pour un personnel
  const generateCard = async (staff) => {
    if (!template || !templatePreview) return null;

    return new Promise((resolve) => {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");
      const img = new Image();

      img.onload = async () => {
        // Configurer le canvas aux dimensions de l'image
        canvas.width = img.width;
        canvas.height = img.height;

        console.log("Image dimensions:", {
          width: img.width,
          height: img.height,
        });
        console.log("DEFAULT_POSITIONS.QR:", DEFAULT_POSITIONS.QR);
        console.log("State qrPosition:", qrPosition);
        console.log("State idPosition:", idPosition);

        // Dessiner le template
        ctx.drawImage(img, 0, 0);

        // Générer et dessiner le QR code - Utiliser le VRAI QR code de la base de données
        const staffQRCode = staff.qr_code || staff.staff_id || staff.unique_id || `${getStaffPrefix(selectedCategory)}${staff.id}`;
        const qrDataUrl = await generateQRCode(staffQRCode);
        
        console.log(`Staff: ${staff.name}, QR Code utilisé: ${staffQRCode}`);
        if (qrDataUrl) {
          const qrImg = new Image();
          qrImg.onload = () => {
            // FORCER l'utilisation des positions du code source
            const qrX = DEFAULT_POSITIONS.QR.x;
            const qrY = DEFAULT_POSITIONS.QR.y;
            const qrSize = DEFAULT_POSITIONS.QR.size;

            console.log(
              `FORCED: Using DEFAULT_POSITIONS - QR at X=${qrX}, Y=${qrY}, Size=${qrSize}`
            );
            console.log(`Drawing QR at: X=${qrX}, Y=${qrY}, Size=${qrSize}`);
            ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

            // FORCER l'utilisation des positions ID du code source
            const idX = DEFAULT_POSITIONS.ID.x;
            const idY = DEFAULT_POSITIONS.ID.y;
            const fontSize = DEFAULT_POSITIONS.ID.fontSize;

            ctx.font = `bold ${fontSize}px Arial`;
            ctx.fillStyle = DEFAULT_POSITIONS.ID.color || "#000000";
            ctx.textAlign = "left";

            console.log(
              `FORCED: Using DEFAULT_POSITIONS - ID at X=${idX}, Y=${idY}, Font=${fontSize}px`
            );

            const idText = staffQRCode; // Utiliser le même QR code que pour la génération
            console.log(
              `Drawing ID "${idText}" at: X=${idX}, Y=${idY}, Font=${fontSize}px`
            );

            ctx.fillText(idText, idX, idY);

            resolve({
              staff: staff,
              cardDataUrl: canvas.toDataURL("image/png"),
              id: staffQRCode, // Utiliser le même QR code cohérent
            });
          };
          qrImg.src = qrDataUrl;
        } else {
          resolve(null);
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
              `✅ Carte ${i + 1}/${selectedStaff.length} générée avec succès pour ${staff.name || staff.first_name + ' ' + staff.last_name}`
            );
          } else {
            console.log(
              `❌ Échec génération carte ${i + 1}/${selectedStaff.length} pour ${staff.name || staff.first_name + ' ' + staff.last_name}`
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
        console.log("✅ Toutes les cartes générées avec succès. Prêt pour le PDF.");
        
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

  // Générer un PDF A4 avec 4 cartes par page (grille 2x2)
  const generatePDFA4 = async (cardsToGenerate = null) => {
    // Utiliser les cartes passées en paramètre ou celles de l'état
    const cardsToUse = cardsToGenerate || previewCards;
    
    console.log("=== GÉNÉRATION PDF A4 ===");
    console.log("Nombre de cartes sélectionnées:", selectedStaff.length);
    console.log("Nombre de cartes à utiliser:", cardsToUse.length);
    console.log("Source des cartes:", cardsToGenerate ? "Paramètre direct" : "previewCards state");
    console.log("cardsToUse:", cardsToUse);

    if (cardsToUse.length === 0) {
      alert("Aucune carte à générer. Veuillez d'abord prévisualiser les cartes.");
      return;
    }

    try {
      setLoading(true);
      
      // Importer jsPDF dynamiquement
      const { jsPDF } = await import('jspdf');
      
      const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
      });

      const pageWidth = 210; // A4 width in mm
      const pageHeight = 297; // A4 height in mm
      
      // Marges minimales pour l'impression (5mm de chaque côté)
      const margin = 5;
      
      // Espacement minimal entre cartes pour faciliter la découpe (2mm)
      const spacing = 2;
      
      // Calcul des dimensions des cartes pour occuper tout l'espace
      const availableWidth = pageWidth - (2 * margin) - spacing; // Espace disponible largeur
      const availableHeight = pageHeight - (2 * margin) - spacing; // Espace disponible hauteur
      
      const cardWidth = availableWidth / 2; // 2 cartes par ligne
      const cardHeight = availableHeight / 2; // 2 lignes par page
      
      console.log(`Dimensions calculées: ${cardWidth.toFixed(1)}mm x ${cardHeight.toFixed(1)}mm par carte`);
      
      // Positions pour 4 cartes par page (grille 2x2) - plein écran
      const positions = [
        { x: margin, y: margin }, // Carte 1: En haut à gauche
        { x: margin + cardWidth + spacing, y: margin }, // Carte 2: En haut à droite  
        { x: margin, y: margin + cardHeight + spacing }, // Carte 3: En bas à gauche
        { x: margin + cardWidth + spacing, y: margin + cardHeight + spacing } // Carte 4: En bas à droite
      ];

      for (let i = 0; i < cardsToUse.length; i++) {
        const card = cardsToUse[i];
        
        // Nouvelle page après chaque 4 cartes (sauf pour la première page)
        if (i > 0 && i % 4 === 0) {
          pdf.addPage();
          console.log(`Nouvelle page créée pour la carte ${i + 1}`);
        }

        // Position sur la page : 0,1,2,3 pour grille 2x2
        const positionIndex = i % 4;
        const position = positions[positionIndex];
        
        console.log(`Carte ${i + 1}: Position ${positionIndex} (${position.x}, ${position.y})`);
        
        try {
          // Convertir le data URL en image et l'ajouter au PDF
          pdf.addImage(
            card.cardDataUrl,
            'PNG',
            position.x,
            position.y,
            cardWidth,
            cardHeight
          );
          
          console.log(`Carte ${i + 1} ajoutée avec succès`);
        } catch (error) {
          console.error(`Erreur ajout carte ${i + 1}:`, error);
        }
      }

      // Ajouter des lignes de découpe pour faciliter la coupe au ciseau
      console.log("Ajout des lignes de découpe...");
      
      // Ligne verticale centrale (entre cartes gauche et droite)
      const centerX = margin + cardWidth + (spacing / 2);
      pdf.setDrawColor(200, 200, 200); // Gris clair
      pdf.setLineWidth(0.1);
      pdf.line(centerX, 0, centerX, pageHeight);
      
      // Ligne horizontale centrale (entre cartes haut et bas)  
      const centerY = margin + cardHeight + (spacing / 2);
      pdf.line(0, centerY, pageWidth, centerY);
      
      // Lignes de bordure (optionnel - pour délimiter la zone de coupe)
      pdf.setDrawColor(150, 150, 150); // Gris plus foncé
      pdf.rect(margin, margin, cardWidth * 2 + spacing, cardHeight * 2 + spacing);

      // Télécharger le PDF
      const filename = `cartes_personnel_${cardsToUse.length}_cartes_${new Date().toISOString().split('T')[0]}.pdf`;
      pdf.save(filename);
      
      alert(`PDF généré avec succès !\n${cardsToUse.length} carte(s) sur ${Math.ceil(cardsToUse.length / 4)} page(s) A4\nDimensions: ${cardWidth.toFixed(1)}mm x ${cardHeight.toFixed(1)}mm par carte\nDisposition: 4 cartes par page (plein écran)\nLignes de découpe incluses pour faciliter la coupe au ciseau`);

    } catch (error) {
      console.error('Erreur génération PDF:', error);
      alert('Erreur lors de la génération du PDF. Assurez-vous que jsPDF est installé.');
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
    }, [templatePreview]);

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

            {/* Indicateur position QR */}
            <div
              className="position-absolute border border-danger bg-danger bg-opacity-25 d-flex align-items-center justify-content-center"
              style={{
                left: `${qrPosition.x * (imageDimensions.scale || 1)}px`,
                top: `${qrPosition.y * (imageDimensions.scale || 1)}px`,
                width: `${qrPosition.size * (imageDimensions.scale || 1)}px`,
                height: `${qrPosition.size * (imageDimensions.scale || 1)}px`,
                cursor: "move",
              }}
            >
              <small className="text-danger fw-bold">QR</small>
            </div>

            {/* Indicateur position ID */}
            <div
              className="position-absolute"
              style={{
                left: `${idPosition.x * (imageDimensions.scale || 1)}px`,
                top: `${idPosition.y * (imageDimensions.scale || 1)}px`,
                fontSize: `${
                  idPosition.fontSize * (imageDimensions.scale || 1)
                }px`,
                color: "red",
                fontWeight: "bold",
                cursor: "move",
                backgroundColor: "rgba(255,255,255,0.8)",
                padding: "2px 4px",
                borderRadius: "2px",
              }}
            >
              ID_SAMPLE
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
                        onClick={() => setShowConfigModal(true)}
                      >
                        <Gear className="me-1" />
                        Configurer positions
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

              {/* Section de gestion des templates */}
              <hr />
              <Row>
                <Col md={6}>
                  <h6>Templates sauvegardés :</h6>
                  {savedTemplates.length > 0 ? (
                    <div className="mb-3">
                      {savedTemplates.map((template, index) => (
                        <div
                          key={index}
                          className="d-flex justify-content-between align-items-center border rounded p-2 mb-2"
                        >
                          <div>
                            <strong>{template.name}</strong>
                            {template.is_default && (
                              <Badge bg="success" className="ms-2">
                                Par défaut
                              </Badge>
                            )}
                            {currentTemplateId === template.id && (
                              <Badge bg="primary" className="ms-2">
                                Actuel
                              </Badge>
                            )}
                          </div>
                          <div>
                            <Button
                              variant="outline-primary"
                              size="sm"
                              className="me-2"
                              onClick={() => loadTemplate(template)}
                            >
                              Charger
                            </Button>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => deleteTemplate(template.id)}
                            >
                              <Trash />
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-muted">Aucun template sauvegardé</p>
                  )}
                </Col>
                <Col md={6}>
                  <div className="d-flex gap-2">
                    <Button
                      variant="success"
                      onClick={() => setShowSaveTemplateModal(true)}
                      disabled={!template || loading}
                    >
                      <Save className="me-1" />
                      {currentTemplateId ? "Mettre à jour" : "Sauvegarder"}
                    </Button>

                    {currentTemplateId && (
                      <Button
                        variant="warning"
                        onClick={() => {
                          setCurrentTemplateId(null);
                          setTemplateName("");
                          setTemplate(null);
                          setTemplatePreview(null);
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
                        }}
                      >
                        Nouveau template
                      </Button>
                    )}
                  </div>
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
                        {staffList.map((staff) => (
                          <Form.Check
                            key={staff.id}
                            type="checkbox"
                            label={`${staff.name} - ${
                              staff.staff_id ||
                              staff.unique_id ||
                              `${getStaffPrefix(selectedCategory)}${staff.id}`
                            }`}
                            checked={selectedStaff.some(
                              (s) => s.id === staff.id
                            )}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setSelectedStaff([...selectedStaff, staff]);
                              } else {
                                setSelectedStaff(
                                  selectedStaff.filter((s) => s.id !== staff.id)
                                );
                              }
                            }}
                            className="mb-2"
                          />
                        ))}
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
                        <Button variant="success" onClick={downloadCards} className="me-2">
                          <Download className="me-2" />
                          Télécharger individuelles ({previewCards.length})
                        </Button>
                        <Button variant="primary" onClick={() => generatePDFA4(window.tempCards)}>
                          <Download className="me-2" />
                          Générer PDF A4 (4 par page - 2x2)
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
              <h6>Aperçu du template avec positions :</h6>
              <TemplateConfigurator />
            </Col>
            <Col md={4}>
              <h6>Position du QR Code :</h6>
              <div className="d-flex gap-2 mb-2">
                <Button
                  variant="outline-info"
                  size="sm"
                  onClick={() => {
                    // Appliquer les positions par défaut du code source
                    console.log(
                      "Applying default positions from source code..."
                    );
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
                  }}
                >
                  Positions exemple
                </Button>
                <Button
                  variant="outline-warning"
                  size="sm"
                  onClick={() => {
                    // Nettoyer le localStorage et réinitialiser
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
                >
                  Réinitialiser tout
                </Button>
              </div>
              <Form.Group className="mb-2">
                <Form.Label>Position X: {qrPosition.x}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="800"
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
                    max="800"
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
                    max="1200"
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
                    max="1200"
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
              <Form.Group className="mb-2">
                <Form.Label>Position X: {idPosition.x}px</Form.Label>
                <div className="d-flex gap-2">
                  <Form.Control
                    type="number"
                    min="0"
                    max="800"
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
                    max="800"
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
                    max="1200"
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
                    max="1200"
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

      {/* Modal Sauvegarde Template */}
      <Modal
        show={showSaveTemplateModal}
        onHide={() => setShowSaveTemplateModal(false)}
      >
        <Modal.Header closeButton>
          <Modal.Title>
            <Save className="me-2" />
            {currentTemplateId
              ? "Mettre à jour le template"
              : "Sauvegarder le template"}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label>Nom du template</Form.Label>
            <Form.Control
              type="text"
              value={templateName}
              onChange={(e) => setTemplateName(e.target.value)}
              placeholder="Ex: Template Personnel 2024"
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Check
              type="checkbox"
              label="Définir comme template par défaut"
              checked={isDefaultTemplate}
              onChange={(e) => setIsDefaultTemplate(e.target.checked)}
            />
            <Form.Text className="text-muted">
              Le template par défaut sera automatiquement chargé au démarrage
            </Form.Text>
          </Form.Group>

          {currentTemplateId && (
            <Alert variant="info">
              <strong>Mode modification :</strong> Ce template sera mis à jour
              avec les nouvelles configurations.
            </Alert>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button
            variant="secondary"
            onClick={() => setShowSaveTemplateModal(false)}
          >
            Annuler
          </Button>
          <Button
            variant="success"
            onClick={saveCurrentTemplate}
            disabled={loading || !templateName.trim()}
          >
            {loading ? (
              <>
                <Spinner animation="border" size="sm" className="me-2" />
                Sauvegarde...
              </>
            ) : (
              <>
                <Save className="me-1" />
                {currentTemplateId ? "Mettre à jour" : "Sauvegarder"}
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>

      <canvas ref={canvasRef} style={{ display: "none" }} />
    </Container>
  );
};

export default CardGenerator;
