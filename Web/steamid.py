import json
from browser import document, html, ajax, bind, console
from browser.widgets.dialog import Dialog, InfoDialog
@bind("strong.class_chatlog", "click")
def onclick(ev):
    def on_complete(req):
        if req.status == 200 or req.status == 0:
            userdata = json.loads(req.text)["data"]

            name = userdata["name"]
            steamid = userdata["auth"]

            left = ev.x
            top = ev.y

            d = Dialog("Name : " + name, ok_cancel=True)

            d.panel <= html.DIV("SteamID : " + steamid)
        else:
            InfoDialog("Error", "It is unable to connect to api server.")
    
    req = ajax.get('./api.php?msg_id=' + str(ev.currentTarget.id), oncomplete=on_complete)

@bind(d.ok_button, "click")
def ok(ev):
    pass
    