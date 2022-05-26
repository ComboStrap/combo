<box align="x-center-children y-center-children" width="1200px" height="600px">
    <box style="width:900px" >
        <grid align="y-children-center" >
            <box width="75%">
                <text font-size="h2" color="muted" >$parentName</text>
                <heading d4 boldness="bold">${title|head(55,"..." )}</heading>
            </box>
            <box>
                <brand align="right" width="70"/>
            </box>
        </grid>
        <text font-size="h1" color="muted">${date_published|format()}</text>
    </box>
</box>

