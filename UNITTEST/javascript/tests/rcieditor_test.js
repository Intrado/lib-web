
describe("rcieditor", function() {

  it("sticks itself on window", function() {
    console.log(window.RCIEditor);
    expect(window.RCIEditor).to.be.a('function');
  });

});
