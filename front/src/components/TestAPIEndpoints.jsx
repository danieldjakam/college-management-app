import React, { useState } from 'react';
import { Button, Card, Container, Row, Col, Alert } from 'react-bootstrap';
import { host } from '../utils/fetch';

const TestAPIEndpoints = () => {
    const [results, setResults] = useState({});
    const [loading, setLoading] = useState(false);

    const testEndpoint = async (name, url) => {
        try {
            console.log(`Testing ${name}: ${url}`);
            
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            return {
                status: response.status,
                success: response.ok,
                data: data,
                count: Array.isArray(data) ? data.length : (data.data?.length || 'N/A'),
                structure: Array.isArray(data) ? data[0] : data
            };
        } catch (error) {
            return {
                status: 'ERROR',
                success: false,
                error: error.message,
                data: null
            };
        }
    };

    const runTests = async () => {
        setLoading(true);
        setResults({});

        const endpoints = [
            { name: 'Users (/api/users)', url: `${host}/api/users` },
            { name: 'Users All (/api/users/all)', url: `${host}/api/users/all` },
            { name: 'Teachers (/api/teachers)', url: `${host}/api/teachers` },
            { name: 'Auth Me (/api/auth/me)', url: `${host}/api/auth/me` },
        ];

        const testResults = {};

        for (const endpoint of endpoints) {
            console.log(`Testing ${endpoint.name}...`);
            testResults[endpoint.name] = await testEndpoint(endpoint.name, endpoint.url);
        }

        setResults(testResults);
        setLoading(false);
    };

    return (
        <Container className="py-4">
            <Row>
                <Col md={12}>
                    <Card>
                        <Card.Header>
                            <h4>Test API Endpoints - Badges Personnel</h4>
                        </Card.Header>
                        <Card.Body>
                            <div className="mb-3">
                                <Button 
                                    onClick={runTests} 
                                    disabled={loading}
                                    variant="primary"
                                >
                                    {loading ? 'Test en cours...' : 'Tester les endpoints API'}
                                </Button>
                            </div>

                            {Object.entries(results).map(([name, result]) => (
                                <Alert 
                                    key={name}
                                    variant={result.success ? 'success' : 'danger'}
                                    className="mb-2"
                                >
                                    <strong>{name}</strong>
                                    <br />
                                    <small>
                                        Status: {result.status} | 
                                        Count: {result.count} | 
                                        Success: {result.success ? 'YES' : 'NO'}
                                    </small>
                                    {result.error && (
                                        <div className="mt-2">
                                            <strong>Error:</strong> {result.error}
                                        </div>
                                    )}
                                    {result.structure && (
                                        <details className="mt-2">
                                            <summary>Structure des données</summary>
                                            <pre className="mt-2 small">
                                                {JSON.stringify(result.structure, null, 2)}
                                            </pre>
                                        </details>
                                    )}
                                </Alert>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
};

export default TestAPIEndpoints;