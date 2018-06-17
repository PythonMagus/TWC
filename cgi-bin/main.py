#!/usr/bin/python
import cgi, re, json, os, time, random
form = cgi.FieldStorage()
now = time.time()
print "Content-type: application/json\n{}"
exit()
try:
    f = open("/home/maneschi/twc.redwaratah.com/db.json")
    db = json.loads(f.read())
except:
    db = {
        "users": [
            {
                "alias": 'PythonMagus',
                "email": "python@redwaratah.com",
                "password": "magus",
                "state": 1,
                "id": 1,
                "victories": 3,
                "battles": 7
            }],
        "ribbons": [],
        "battles": [],
        "sessions": {}
    }
for session, rec in db["sessions"].items():
    if rec.now +3600 < rec["lastUpdate"]:
        del db["sessions"][session]

def handleLogin():
    if 'userName' not in form:
        print 'Content-type: application/json\n{"noLogin": 1}'
        return
    if validLogin(form["userName"], form["password"]):
        print 'Content-type: application/json\n{"show": "users.htm", "session": session}'

def validLogin(name, pw):
    global session
    for rec in db["users"]:
        if rec["email"] == name and rec["password"] == pw:
            session = hex(random.randrange(0x10000000,0xFFFFFFFF))
            db["sessions"][session] = {"session": session, "userId": rec["id"], "lastUpdate": now}
            return True
    return False

session = form["session"] if "session" in form else None
if session:
    if session not in db["sessions"]:
        session = None
if not session:
    handleLogin()
    exit()

action = form["action"] if "action" in form else "Unknown"

f = open("/home/maneschi/twc.redwaratah.com/db.json", 'w')
f.write(json.dumps(db))
                     
print "Content-type: application/json\n{}"