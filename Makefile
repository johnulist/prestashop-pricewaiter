.PHONY: clean all

all: pricewaiter.zip

pricewaiter.zip: *
	git archive HEAD --prefix pricewaiter/ --format=zip -o pricewaiter.zip

clean:
	rm -f pricewaiter.zip
