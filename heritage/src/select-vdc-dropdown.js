/**
 * Created by User on 6/4/2015.
 */
parentId = 'district_name';
childId = 'vdc_name';
var selDistName;
var selVdcName;
for (var i = 0; i < Districts.District.length; i++) {
    $('#' + parentId).append($('<option></option>').val(Districts.District[i]).html(Districts.District[i]));
}
$('#' + parentId).change(function () {
    selDistName = $('#' + parentId).val();
    if (selDistName != '') {
        distVDCs = getDistrictVDCs(selDistName);
        $('#' + childId).html('');
        $('#' + childId).append($('<option></option>').val('').html('Select VDC/Municipality'));
        for (var i = 0; i < distVDCs.length; i++) {
            $('#' + childId).append(
                $('<option></option>').val(distVDCs[i]).html(distVDCs[i]));
        }
    } else {
        $('#' + childId).html('');
        $('#' + childId).append($('<option></option>').val('').html('Select VDC/Municipality'));
    }
});

$('#' + childId).change(function () {
    selVdcName = $('#' + childId).val();
});
