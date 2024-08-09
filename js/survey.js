/**
 * Initialize the formbricks survey.
 * 
 * @see https://github.com/formbricks/setup-examples/tree/main/html
 */
window.addEventListener('themeisle:survey:loaded', function () {
    window?.tsdk_formbricks?.init?.({
        environmentId: "clza3s4zm000h10km1699nlli",
        apiHost: "https://app.formbricks.com",
        ...(window?.PPOMSurveyData ?? {}),
    });
});    