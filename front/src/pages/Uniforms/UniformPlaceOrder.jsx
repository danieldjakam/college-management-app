import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  Button,
  Badge,
  Form,
  Table,
  Alert,
  Spinner,
  Modal
} from 'react-bootstrap';
import {
  Cart,
  Plus,
  Dash,
  Trash,
  ShoppingBag,
  Search
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import Swal from 'sweetalert2';

const UniformPlaceOrder = () => {
  const [loading, setLoading] = useState(false);
  const [items, setItems] = useState([]);
  const [cart, setCart] = useState([]);
  const [students, setStudents] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [studentSearch, setStudentSearch] = useState('');
  const [showCart, setShowCart] = useState(false);
  const [orderNotes, setOrderNotes] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');

  useEffect(() => {
    fetchItems();
    fetchStudents();
  }, []);

  const fetchItems = async () => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getUniformItems({ active_only: true });
      if (response.success) {
        setItems(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement articles:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchStudents = async () => {
    try {
      const response = await apiEndpoints.getStudents();
      if (response.success) {
        setStudents(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement élèves:', error);
    }
  };

  const filteredStudents = students.filter(
    (student) =>
      student.first_name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      student.last_name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      student.matricule.toLowerCase().includes(studentSearch.toLowerCase())
  );

  const filteredItems = items.filter(
    (item) => categoryFilter === 'all' || item.category === categoryFilter
  );

  const addToCart = (variant, item) => {
    if (variant.available_quantity < 1) {
      Swal.fire({
        icon: 'warning',
        title: 'Stock épuisé',
        text: 'Cet article n\'est plus disponible'
      });
      return;
    }

    const existingItem = cart.find((cartItem) => cartItem.variant_id === variant.id);

    if (existingItem) {
      if (existingItem.quantity >= variant.available_quantity) {
        Swal.fire({
          icon: 'warning',
          title: 'Stock insuffisant',
          text: `Seulement ${variant.available_quantity} article(s) disponible(s)`
        });
        return;
      }
      setCart(
        cart.map((cartItem) =>
          cartItem.variant_id === variant.id
            ? { ...cartItem, quantity: cartItem.quantity + 1 }
            : cartItem
        )
      );
    } else {
      setCart([
        ...cart,
        {
          variant_id: variant.id,
          item_name: item.name,
          size: variant.size,
          price: variant.final_price,
          quantity: 1,
          available: variant.available_quantity,
          item_image: item.image_url
        }
      ]);
    }
  };

  const updateCartQuantity = (variantId, newQuantity) => {
    const cartItem = cart.find((item) => item.variant_id === variantId);

    if (newQuantity > cartItem.available) {
      Swal.fire({
        icon: 'warning',
        title: 'Stock insuffisant',
        text: `Seulement ${cartItem.available} article(s) disponible(s)`
      });
      return;
    }

    if (newQuantity < 1) {
      removeFromCart(variantId);
      return;
    }

    setCart(
      cart.map((item) =>
        item.variant_id === variantId ? { ...item, quantity: newQuantity } : item
      )
    );
  };

  const removeFromCart = (variantId) => {
    setCart(cart.filter((item) => item.variant_id !== variantId));
  };

  const getTotalAmount = () => {
    return cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  };

  const handlePlaceOrder = async () => {
    if (!selectedStudent) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Veuillez sélectionner un élève'
      });
      return;
    }

    if (cart.length === 0) {
      Swal.fire({
        icon: 'error',
        title: 'Panier vide',
        text: 'Veuillez ajouter des articles au panier'
      });
      return;
    }

    try {
      setLoading(true);

      const orderData = {
        student_id: selectedStudent.id,
        items: cart.map((item) => ({
          variant_id: item.variant_id,
          quantity: item.quantity
        })),
        notes: orderNotes
      };

      const response = await apiEndpoints.placeUniformOrder(orderData);

      if (response.success) {
        Swal.fire({
          icon: 'success',
          title: 'Commande créée!',
          html: `
            <p>Commande <strong>${response.data.order_number}</strong> créée avec succès</p>
            <p class="text-muted mt-2">L'élève sera notifié quand la commande sera prête pour retrait</p>
          `,
          timer: 3000
        });

        // Reset
        setCart([]);
        setSelectedStudent(null);
        setStudentSearch('');
        setOrderNotes('');
        setShowCart(false);

        // Refresh items to update stock
        fetchItems();
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: error.message || 'Erreur lors de la création de la commande'
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <ShoppingBag className="me-2" style={{ color: '#007bff' }} />
            Passer une Commande - Tenues Scolaires
          </h2>
          <p className="text-muted">
            Sélectionnez un élève et ajoutez des articles au panier
          </p>
        </Col>
        <Col xs="auto">
          <Button
            variant="primary"
            size="lg"
            onClick={() => setShowCart(true)}
            disabled={cart.length === 0}
          >
            <Cart className="me-2" />
            Panier ({cart.length})
            {cart.length > 0 && (
              <Badge bg="light" text="dark" className="ms-2">
                {new Intl.NumberFormat('fr-FR').format(getTotalAmount())} FCFA
              </Badge>
            )}
          </Button>
        </Col>
      </Row>

      {/* Student Selection */}
      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-info text-white">
              <h5 className="mb-0">1. Sélectionner l'Élève</h5>
            </Card.Header>
            <Card.Body>
              <Form.Group className="mb-3">
                <Form.Control
                  type="text"
                  placeholder="Rechercher par nom, prénom ou matricule..."
                  value={studentSearch}
                  onChange={(e) => setStudentSearch(e.target.value)}
                />
              </Form.Group>

              {selectedStudent ? (
                <Alert variant="success">
                  <strong>Élève sélectionné:</strong> {selectedStudent.first_name}{' '}
                  {selectedStudent.last_name} ({selectedStudent.matricule})
                  <Button
                    variant="outline-danger"
                    size="sm"
                    className="ms-3"
                    onClick={() => setSelectedStudent(null)}
                  >
                    Changer
                  </Button>
                </Alert>
              ) : (
                <div style={{ maxHeight: '200px', overflowY: 'auto' }}>
                  {filteredStudents.length === 0 ? (
                    <Alert variant="info">Aucun élève trouvé</Alert>
                  ) : (
                    <div className="list-group">
                      {filteredStudents.slice(0, 10).map((student) => (
                        <button
                          key={student.id}
                          className="list-group-item list-group-item-action"
                          onClick={() => {
                            setSelectedStudent(student);
                            setStudentSearch('');
                          }}
                        >
                          {student.first_name} {student.last_name} -{' '}
                          <small className="text-muted">{student.matricule}</small>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-warning text-white">
              <h5 className="mb-0">2. Ajouter des Articles</h5>
            </Card.Header>
            <Card.Body>
              <Form.Group>
                <Form.Label>Filtrer par catégorie</Form.Label>
                <Form.Select
                  value={categoryFilter}
                  onChange={(e) => setCategoryFilter(e.target.value)}
                >
                  <option value="all">Toutes les catégories</option>
                  <option value="sport">Tenue de sport</option>
                  <option value="mercredi">Tenue de mercredi</option>
                  <option value="autre">Autre</option>
                </Form.Select>
              </Form.Group>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Catalog */}
      <Row>
        <Col>
          <h4 className="mb-3">Catalogue des Articles</h4>
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" />
              <p className="mt-2">Chargement...</p>
            </div>
          ) : filteredItems.length === 0 ? (
            <Alert variant="info">Aucun article disponible</Alert>
          ) : (
            <Row>
              {filteredItems.map((item) => (
                <Col key={item.id} md={6} lg={4} className="mb-4">
                  <Card className="h-100 shadow-sm">
                    {item.image_url && (
                      <Card.Img
                        variant="top"
                        src={item.image_url}
                        style={{ height: '200px', objectFit: 'cover' }}
                      />
                    )}
                    <Card.Body>
                      <Card.Title>{item.name}</Card.Title>
                      <Card.Text className="text-muted">
                        {item.description || 'Aucune description'}
                      </Card.Text>
                      <div className="mb-3">
                        <Badge bg="secondary">{item.category || 'Non catégorisé'}</Badge>
                        {item.needs_restock && (
                          <Badge bg="danger" className="ms-2">
                            Stock bas
                          </Badge>
                        )}
                      </div>

                      <h5 className="text-primary">
                        {new Intl.NumberFormat('fr-FR').format(item.base_price)} FCFA
                      </h5>

                      <hr />

                      <div>
                        <strong>Tailles disponibles:</strong>
                        <div className="mt-2">
                          {item.variants && item.variants.length > 0 ? (
                            item.variants.map((variant) => (
                              <Button
                                key={variant.id}
                                variant="outline-primary"
                                size="sm"
                                className="me-2 mb-2"
                                onClick={() => addToCart(variant, item)}
                                disabled={variant.available_quantity < 1 || !selectedStudent}
                              >
                                {variant.size}
                                <Badge
                                  bg={variant.available_quantity > 5 ? 'success' : 'warning'}
                                  className="ms-2"
                                >
                                  {variant.available_quantity}
                                </Badge>
                              </Button>
                            ))
                          ) : (
                            <Alert variant="warning" className="mb-0">
                              Aucune taille disponible
                            </Alert>
                          )}
                        </div>
                      </div>
                    </Card.Body>
                  </Card>
                </Col>
              ))}
            </Row>
          )}
        </Col>
      </Row>

      {/* Cart Modal */}
      <Modal show={showCart} onHide={() => setShowCart(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <Cart className="me-2" />
            Panier ({cart.length} article{cart.length > 1 ? 's' : ''})
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedStudent && (
            <Alert variant="info">
              <strong>Commande pour:</strong> {selectedStudent.first_name}{' '}
              {selectedStudent.last_name} ({selectedStudent.matricule})
            </Alert>
          )}

          {cart.length === 0 ? (
            <Alert variant="warning">Le panier est vide</Alert>
          ) : (
            <>
              <Table responsive>
                <thead>
                  <tr>
                    <th>Article</th>
                    <th>Taille</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {cart.map((item) => (
                    <tr key={item.variant_id}>
                      <td>{item.item_name}</td>
                      <td>
                        <Badge bg="secondary">{item.size}</Badge>
                      </td>
                      <td>{new Intl.NumberFormat('fr-FR').format(item.price)} FCFA</td>
                      <td>
                        <div className="d-flex align-items-center">
                          <Button
                            variant="outline-secondary"
                            size="sm"
                            onClick={() =>
                              updateCartQuantity(item.variant_id, item.quantity - 1)
                            }
                          >
                            <Dash />
                          </Button>
                          <span className="mx-3">{item.quantity}</span>
                          <Button
                            variant="outline-secondary"
                            size="sm"
                            onClick={() =>
                              updateCartQuantity(item.variant_id, item.quantity + 1)
                            }
                          >
                            <Plus />
                          </Button>
                        </div>
                      </td>
                      <td className="fw-bold">
                        {new Intl.NumberFormat('fr-FR').format(
                          item.price * item.quantity
                        )}{' '}
                        FCFA
                      </td>
                      <td>
                        <Button
                          variant="outline-danger"
                          size="sm"
                          onClick={() => removeFromCart(item.variant_id)}
                        >
                          <Trash />
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot>
                  <tr>
                    <th colSpan="4" className="text-end">
                      TOTAL:
                    </th>
                    <th colSpan="2" className="text-primary fs-5">
                      {new Intl.NumberFormat('fr-FR').format(getTotalAmount())} FCFA
                    </th>
                  </tr>
                </tfoot>
              </Table>

              <Form.Group className="mt-3">
                <Form.Label>Notes (Optionnel)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  value={orderNotes}
                  onChange={(e) => setOrderNotes(e.target.value)}
                  placeholder="Remarques ou informations supplémentaires..."
                />
              </Form.Group>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowCart(false)}>
            Continuer les achats
          </Button>
          <Button
            variant="success"
            onClick={handlePlaceOrder}
            disabled={loading || cart.length === 0 || !selectedStudent}
          >
            {loading ? (
              <>
                <Spinner size="sm" className="me-2" />
                Traitement...
              </>
            ) : (
              <>Passer la commande</>
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default UniformPlaceOrder;
