function underscore_to_camel_case(string, capitalize_first_letter) {
    out = "";
    string = string.split("_");
    for (i in string) {
        for (j in string[i]) {
            if ((i == 0 && j == 0 && capitalize_first_letter) || (i != 0 && j == 0)) {
                out += string[i][j].toUpperCase();
            } else {
                out += string[i][j].toLowerCase();
            }
        }
    }
    return out;
}