import json
from browser import document, html, ajax, bind, console, window
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

            d = Dialog("Name : " + name, ok_cancel=True, top=top, left=left)

            d.panel <= html.DIV("SteamID :" + html.INPUT(type="text", value=steamid, readonly="readonly"))
            d.panel <= html.DIV("Click okay if you want to get redirected to steamrep.")

            @bind(d.ok_button, "click")
            def ok(ev):
                steamid = d.select_one("INPUT").value

                window.location.href = "https://steamrep.com/search?q=" + steamid
                pass

        else:
            InfoDialog("Error", "It is unable to connect to api server.")
    
    ajax.get('./api.php?msg_id=' + str(ev.currentTarget.id), oncomplete=on_complete)
    