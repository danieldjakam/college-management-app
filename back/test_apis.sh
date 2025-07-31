#!/bin/bash

# Function to get fresh token
get_token() {
    curl -s -X POST "http://127.0.0.1:4000/api/auth/login" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{"username": "superadmin", "password": "admin123"}' | \
      python3 -c "import sys, json; print(json.load(sys.stdin)['access_token'])"
}

# Function to make API call
api_call() {
    local endpoint=$1
    local method=${2:-GET}
    local data=${3:-}
    
    local token=$(get_token)
    
    if [ -n "$data" ]; then
        curl -s -X $method "http://127.0.0.1:4000/api/$endpoint" \
          -H "Accept: application/json" \
          -H "Content-Type: application/json" \
          -H "Authorization: Bearer $token" \
          -d "$data"
    else
        curl -s -X $method "http://127.0.0.1:4000/api/$endpoint" \
          -H "Accept: application/json" \
          -H "Authorization: Bearer $token"
    fi
}

echo "=== Testing API Endpoints ==="

echo -e "\n1. Testing series-subjects:"
api_call "series-subjects" | python3 -m json.tool

echo -e "\n2. Testing teachers:"
api_call "teachers" | python3 -m json.tool

echo -e "\n3. Testing teacher-assignments:"
api_call "teacher-assignments" | python3 -m json.tool

echo -e "\n4. Testing school years:"
api_call "school-years/active" | python3 -m json.tool

echo -e "\n5. Testing main teachers:"
api_call "main-teachers" | python3 -m json.tool

echo -e "\n6. Testing available subjects for teacher 1:"
api_call "teacher-assignments/teacher/1/available-subjects" | python3 -m json.tool

echo -e "\n=== End of Tests ==="