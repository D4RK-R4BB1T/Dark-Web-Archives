import 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.featuregroup.subgroup';

function escape(unsafe)
{
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

let base_url = window._base_url;

L.Icon.Default.imagePath = base_url + '/assets/img/vendor/leaflet/dist/';
let map = L.map('map', {attributionControl: false}).setView([66, 105], 2);
L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png').addTo(map);

let data = window._map_data;
let goods = data['goods'];
let quests = data['quests'];

let layerControl = L.control.layers().addTo(map);
let markerCluster = L.markerClusterGroup().addTo(map);
for (let quest of quests) {
    let good = goods.find(obj => { return obj.id === quest['good_id'] });

    if (!good.hasOwnProperty('group')) {
        good.group = L.featureGroup.subGroup(markerCluster).addTo(map);
        layerControl.addOverlay(good.group, escape(good['title']));
    }

    let text = ''
        + '<p class="text-center"><a href="' + base_url + '/shop/management/goods/quests/view/' + good['id'] + '/' + quest['id'] + '">Просмотреть квест</a></p>'
        + 'Товар: ' + escape(good['title']) + '<br>'
        + 'Количество: ' + quest['amount'] + '<br>';

    let m = L.marker(quest['geo'], { opacity: .6 }).bindPopup(text);
    good.group.addLayer(m);
}
