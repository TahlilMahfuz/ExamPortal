#include <stdio.h>
#include <jansson.h>

// Function to create and return a JSON array of integers
json_t *create_json_array() {
    int array[] = {10, 20, 30, 40, 50};
    size_t array_size = sizeof(array) / sizeof(array[0]);

    // Create a JSON array
    json_t *json_array = json_array();

    // Add elements to the JSON array
    for (size_t i = 0; i < array_size; ++i) {
        json_t *json_integer = json_integer(array[i]);
        json_array_append_new(json_array, json_integer);
    }

    return json_array;
}

int main() {
    // Initialize jansson
    json_t *json_array;

    // Create a JSON array
    json_array = create_json_array();

    // Print the JSON array
    char *json_array_string = json_dumps(json_array, JSON_INDENT(4));
    if (json_array_string == NULL) {
        fprintf(stderr, "Error: Unable to create JSON object.\n");
        return 1;
    }
    
    printf("%s\n", json_array_string);

    // Cleanup
    json_decref(json_array);
    free(json_array_string);

    return 0;
}

