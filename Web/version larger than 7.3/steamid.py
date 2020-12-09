import json
from browser import document, html, ajax, bind, console, window # pylint: disable=import-error
from browser.widgets.dialog import Dialog, InfoDialog # pylint: disable=import-error

def prompt_steamid_dialog(msg_id, x, y):
    def on_complete(req):
        if req.status == 200 or req.status == 0:
            userdata = json.loads(req.text)["data"]

            name = userdata["name"]
            steamid = userdata["auth"]

            left = x
            top = y

            d = Dialog("Name : " + name, ok_cancel=True, top=top, left=left)

            d.panel <= html.DIV("SteamID :" + html.INPUT(type="text", value=steamid, readonly="readonly", style={"width":"200px"}), style={"width":"auto"})
            d.panel <= html.DIV("Click okay to get page redirected to steamrep.")

            def onclick(ev):
                steamid = d.select_one("INPUT").value

                window.location.href = "https://steamrep.com/search?q=" + steamid
            
            d.ok_button.bind("click", onclick)

        else:
            InfoDialog("Error", "It is unable to connect to api server.")
    
    ajax.get('./api.php?msg_id=' + str(msg_id), oncomplete=on_complete)

if __name__.startswith("__main__"):
    @bind("strong.class_chatlog", "click")
    def onclick(ev):
        prompt_steamid_dialog(ev.currentTarget.id, ev.clientX, ev.clientY)
    
