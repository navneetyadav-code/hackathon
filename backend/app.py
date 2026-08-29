from flask import Flask, jsonify, request
from flask_cors import CORS
import sqlite3
import os

app = Flask(__name__)
CORS(app)

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DATABASE_DIR = os.path.join(BASE_DIR, "database")
DATABASE = os.path.join(DATABASE_DIR, "thikana.db")

os.makedirs(DATABASE_DIR, exist_ok=True)


# -----------------------------
# DATABASE
# -----------------------------

def get_db():
    conn = sqlite3.connect(DATABASE)
    conn.row_factory = sqlite3.Row
    return conn


def init_db():
    conn = get_db()

    conn.execute("""
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL,
            rent INTEGER NOT NULL,
            room_type TEXT NOT NULL,
            description TEXT,
            image TEXT,
            match_score INTEGER DEFAULT 80
        )
    """)

    conn.execute("""
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender TEXT NOT NULL,
            receiver TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)

    # Add sample rooms only if database is empty
    count = conn.execute(
        "SELECT COUNT(*) FROM rooms"
    ).fetchone()[0]

    if count == 0:
        sample_rooms = [
            (
                "Green View PG",
                "Phagwara",
                6500,
                "shared",
                "Shared room with food and Wi-Fi available.",
                "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80",
                94
            ),
            (
                "City Heights Flat",
                "Phagwara",
                9500,
                "private",
                "Private room with Wi-Fi included.",
                "https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80",
                88
            ),
            (
                "Student Residency",
                "Phagwara",
                5800,
                "shared",
                "Shared accommodation with electricity included.",
                "https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=800&q=80",
                91
            )
        ]

        conn.executemany("""
            INSERT INTO rooms
            (name, location, rent, room_type, description, image, match_score)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        """, sample_rooms)

    conn.commit()
    conn.close()


# -----------------------------
# HOME
# -----------------------------

@app.route("/")
def home():
    return jsonify({
        "message": "Thikana backend is running!"
    })


# -----------------------------
# GET ROOMS
# -----------------------------

@app.route("/api/rooms", methods=["GET"])
def get_rooms():

    location = request.args.get("location", "")
    max_budget = request.args.get("max_budget", "")
    room_type = request.args.get("room_type", "")

    conn = get_db()

    query = "SELECT * FROM rooms WHERE 1=1"
    params = []

    if location:
        query += " AND (name LIKE ? OR location LIKE ?)"
        params.extend([
            f"%{location}%",
            f"%{location}%"
        ])

    if max_budget and max_budget != "all":
        query += " AND rent <= ?"
        params.append(int(max_budget))

    if room_type and room_type != "all":
        query += " AND room_type = ?"
        params.append(room_type)

    query += " ORDER BY match_score DESC"

    rooms = conn.execute(query, params).fetchall()

    conn.close()

    return jsonify([
        dict(room)
        for room in rooms
    ])


# -----------------------------
# ADD ROOM
# -----------------------------

@app.route("/api/rooms", methods=["POST"])
def add_room():

    data = request.json

    required = [
        "name",
        "location",
        "rent",
        "room_type"
    ]

    for field in required:
        if field not in data:
            return jsonify({
                "error": f"{field} is required"
            }), 400

    conn = get_db()

    cursor = conn.execute("""
        INSERT INTO rooms
        (name, location, rent, room_type, description, image, match_score)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    """, (
        data["name"],
        data["location"],
        data["rent"],
        data["room_type"],
        data.get("description", ""),
        data.get("image", ""),
        data.get("match_score", 80)
    ))

    conn.commit()

    room_id = cursor.lastrowid

    conn.close()

    return jsonify({
        "success": True,
        "room_id": room_id
    }), 201


# -----------------------------
# SMART MATCH
# -----------------------------

@app.route("/api/match", methods=["POST"])
def smart_match():

    data = request.json

    income = float(data.get("income", 0))
    utility = float(data.get("utility", 0))
    room_type = data.get("room_type", "standard")

    if income <= 0:
        return jsonify({
            "error": "Income must be greater than 0"
        }), 400

    conn = get_db()

    rooms = conn.execute("""
        SELECT * FROM rooms
    """).fetchall()

    results = []

    for room in rooms:

        if room_type == "master":
            share = room["rent"] * 0.6
        else:
            share = room["rent"] * 0.5

        share += utility / 3

        percentage = (share / income) * 100

        if percentage <= 30:
            status = "Great fit"
        elif percentage <= 40:
            status = "Affordable"
        elif percentage <= 50:
            status = "A little expensive"
        else:
            status = "Over your target"

        results.append({
            "id": room["id"],
            "name": room["name"],
            "share": round(share),
            "percentage": round(percentage, 1),
            "status": status
        })

    conn.close()

    results.sort(key=lambda x: x["percentage"])

    return jsonify(results)


# -----------------------------
# SEND MESSAGE
# -----------------------------

@app.route("/api/messages", methods=["POST"])
def send_message():

    data = request.json

    sender = data.get("sender", "User")
    receiver = data.get("receiver", "Host")
    message = data.get("message", "").strip()

    if not message:
        return jsonify({
            "error": "Message cannot be empty"
        }), 400

    conn = get_db()

    cursor = conn.execute("""
        INSERT INTO messages
        (sender, receiver, message)
        VALUES (?, ?, ?)
    """, (
        sender,
        receiver,
        message
    ))

    conn.commit()

    message_id = cursor.lastrowid

    conn.close()

    return jsonify({
        "success": True,
        "message_id": message_id
    }), 201


# -----------------------------
# GET MESSAGES
# -----------------------------

@app.route("/api/messages", methods=["GET"])
def get_messages():

    sender = request.args.get("sender", "User")
    receiver = request.args.get("receiver", "Host")

    conn = get_db()

    messages = conn.execute("""
        SELECT *
        FROM messages
        WHERE
            (sender = ? AND receiver = ?)
            OR
            (sender = ? AND receiver = ?)
        ORDER BY created_at ASC
    """, (
        sender,
        receiver,
        receiver,
        sender
    )).fetchall()

    conn.close()

    return jsonify([
        dict(message)
        for message in messages
    ])


# -----------------------------
# START SERVER
# -----------------------------

if __name__ == "__main__":
    init_db()

    print("================================")
    print(" THIKANA BACKEND")
    print(" Server running on port 5000")
    print("================================")

    app.run(
        host="0.0.0.0",
        port=5000,
        debug=True
    )

