import ComboModal from "../ComboModal";


test('Combo', () => {

    let modal = ComboModal.createTemporary();
    let htmlBody = "<p>Body</p>";
    modal.addBody(htmlBody)
    modal.build();
    let modalElement = modal.getElement();
    expect(modalElement.innerHTML).toEqualHtmlString("")

});
